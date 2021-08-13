<?php

namespace App\Controller;

use App\Entity\CompanyProcess;
use App\Entity\CompanyProcessStep;
use App\Entity\State;
use App\Entity\Step;
use App\Form\CompanyProcessStepsType;
use App\Form\CompanyProcessType;
use App\Repository\CompanyProcessRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AllProcessController extends AbstractController
{
    /**
     * @Route("/allprocess", name="allprocess_index", methods={"GET"})
     */
    public function index(CompanyProcessRepository $companyProcess): Response
    {
        return $this->render('allprocess/index.html.twig', [
            'processes' => $companyProcess->findAll(),
        ]);
    }

    /**
     * @Route("/companyprocess/new", name="companyprocess_new", methods={"GET", "POST"})
     */
    public function new(Request $request): Response
    {
        $companyProcess = new CompanyProcess();
        $form = $this->createForm(CompanyProcessType::class, $companyProcess);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $companyProcess->setCreatedAt(new \DateTimeImmutable());
            $companyProcess->setUpdatedAt(new \DateTimeImmutable());
            $companyProcess->setIsFinished(false);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($companyProcess);
            $entityManager->flush();

            return $this->redirectToRoute('allprocess_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('company/new.html.twig', [
            'companyProcess' => $companyProcess,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/companyprocess/{id}", name="companyprocess_show", methods={"GET"})
     */
    public function show(CompanyProcess $companyProcess): Response
    {
        return $this->render('companyprocess/show.html.twig', [
            'companyProcess' => $companyProcess,
        ]);
    }

    /**
     * @Route("/edit/{id}", name="companyprocess_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, CompanyProcess $companyProcess): Response
    {
        if ($companyProcess->getState()->getIsFinalState() === true) {
            return $this->redirectToRoute("allprocess_index");
        }
        $Steps = $companyProcess->getCompanyProcessSteps();
        foreach ($Steps as $step) {
            $currentStep = $step->getStep();
        }

        if (isset($currentStep)) {
            $nextStep = $this->getDoctrine()->getRepository(Step::class)->findOneBy([
                'weight' => $currentStep->getWeight() + 1,
                'process' => $currentStep->getProcess()
            ]);
        } else {
            $nextStep = $this->getDoctrine()->getRepository(Step::class)->findOneBy([
                'weight' => 1,
                'process' => $companyProcess->getProcess()
            ]);

        }



        $form = $this->createForm(CompanyProcessStepsType::class, $nextStep);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $return = $form->get("Step")->getData();

            if (count($companyProcess->getCompanyProcessSteps())) {
                $companyProcess->setIsFinished(true);
                $companyProcess->setState($this->getDoctrine()->getManager()->getRepository(State::class)->findOneBy(["IsFinalState" => true]));
                return $this->redirectToRoute("allprocess_index");
            }

            if ($return !== null) {
                $companyProcess->setUpdatedAt(new \DateTimeImmutable());
                $companyProcessStep = new CompanyProcessStep();
                $companyProcessStep
                    ->setCompanyProcess($companyProcess)
                    ->setStep($nextStep)
                    ->setValidatedAt(new \DateTimeImmutable())
                    ->setValidatedBy($this->getUser());

                $this->getDoctrine()->getManager()->persist($companyProcessStep);
                $this->getDoctrine()->getManager()->flush();


                return $this->redirectToRoute('companyprocess_edit', [
                    'id' => $companyProcess->getId()
                ]);
            }

        }

        return $this->renderForm('companyprocess/edit.html.twig', [
            'companyProcess' => $companyProcess,
            'form' => $form,
        ]);
    }


}
