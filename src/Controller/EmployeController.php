<?php

namespace App\Controller;

use App\Entity\Employe;
use App\Form\RegisterType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\EmployeRepository;
use App\Form\EmployeType;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class EmployeController extends AbstractController
{
    public function __construct(
        private EmployeRepository $employeRepository,
        private EntityManagerInterface $entityManager,
    )
    {

    }

    #[Route('/employes', name: 'app_employes')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function employes(): Response
    {
        $employes = $this->employeRepository->findAll();
        
        return $this->render('employe/liste.html.twig', [
            'employes' => $employes,
            'admin' => $this->isGranted('ROLE_ADMIN')
        ]);
    }

    #[Route('/employes/{id}/supprimer', name: 'app_employe_delete')]
    #[IsGranted('ROLE_ADMIN')]
    public function supprimerEmploye($id): Response
    {
        $employe = $this->employeRepository->find($id);

        if(!$employe) {
            return $this->redirectToRoute('app_employes');
        }

        $this->entityManager->remove($employe);
        $this->entityManager->flush();
        
        return $this->redirectToRoute('app_employes');
    }

    #[Route('/employes/{id}/editer', name: 'app_employe_edit')]
    #[IsGranted('ROLE_ADMIN')]
    public function editerEmploye($id, Request $request): Response
    {
        $employe = $this->employeRepository->find($id);

        if(!$employe) {
            return $this->redirectToRoute('app_employes');
        }

        $form = $this->createForm(EmployeType::class, $employe);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            return $this->redirectToRoute('app_employes');
        }

        return $this->render('employe/employe.html.twig', [
            'employe' => $employe,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/connexion/incription', name: 'app_employe_incription')]
    public function register(Request $request, UserPasswordHasherInterface $hasher): Response
    {
        $emp = new Employe();
        $form = $this->createForm(RegisterType::class, $emp);
        $emp->setDateArrivee(new DateTime());
        $emp->setStatut('N/A');
        
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Employe $emp */
            $emp = $form->getData();
            $emp->setPassword($hasher->hashPassword($emp, $emp->getPassword()));

            $this->entityManager->persist($emp);
            $this->entityManager->flush();
            return $this->redirectToRoute('app_projets');
        }

        return $this->render('employe/inscription.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
