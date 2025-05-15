<?php

namespace App\Controller;

use App\Entity\Course;
use App\Exception\BillingUnavailableException;
use App\Form\CourseType;
use App\Helpers\CourseHelper;
use App\Repository\CourseRepository;
use App\Service\BillingClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/courses')]
class CourseController extends AbstractController
{
    public function __construct(
        private BillingClient $billingClient,
    ) {
    }
    
    #[Route('/', name: 'app_course_index', methods: ['GET'])]
    public function index(
        CourseRepository $courseRepository
    ): Response {
        $coursesAll = $courseRepository->findAll();
        $courseResponse = $this->billingClient->courses();

        $courses = CourseHelper::merge($courseResponse, $coursesAll);
        
        $user = $this->getUser();

        if ($user !== null) {
            $transactions = $this->billingClient->transactions(
                $user->getApiToken(),
                ['skip_expired' => true, 'type' => 'payment']
            );

            $courses = CourseHelper::addTransactions($courses, $transactions);
        }

        return $this->render('course/index.html.twig', [
            'courses' => $courses,
        ]);
    }

    #[IsGranted('ROLE_SUPER_ADMIN')]
    #[Route('/new', name: 'app_course_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $course = new Course();
        $form = $this->createForm(CourseType::class, $course);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($course);
            $entityManager->flush();

            return $this->redirectToRoute('app_course_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('course/new.html.twig', [
            'course' => $course,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_course_show', methods: ['GET'])]
    public function show(Course $course): Response
    {
        $courseResponse = $this->billingClient->course($course->getCode());
        $courseResult = CourseHelper::merge([$courseResponse], [$course]);

        $user = $this->getUser();

        if ($user !== null) {
            $transactions = $this->billingClient->transactions(
                $user->getApiToken(),
                [
                    'skip_expired' => true,
                    'type' => 'payment',
                    'course_code' => $course->getCode()
                ]
            );

            $courseResult = CourseHelper::addTransactions($courseResult, $transactions);
        }

        $disabled = false;
        if ($courseResult[0]['type'] !== 'free') {
            if ($user !== null) {
                $disabled = ($user->getBalance() < $courseResult[0]['price']);
            } else {
                $disabled = true;
            }
        }

        return $this->render('course/show.html.twig', [
            'course' => $courseResult[0],
            'disabled' => $disabled,
        ]);
    }

    #[IsGranted('ROLE_SUPER_ADMIN')]
    #[Route('/{id}/edit', name: 'app_course_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Course $course, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CourseType::class, $course);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_course_show', ["id" => $course->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('course/edit.html.twig', [
            'course' => $course,
            'form' => $form,
        ]);
    }
    
    #[IsGranted('ROLE_SUPER_ADMIN')]
    #[Route('/{id}', name: 'app_course_delete', methods: ['POST'])]
    public function delete(Request $request, Course $course, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$course->getId(), $request->request->get('_token'))) {
            $entityManager->remove($course);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_course_index', [], Response::HTTP_SEE_OTHER);
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/{id}/buy', name: 'app_course_buy', methods: ['POST'])]
    public function buy(Course $course): Response
    {
        $user = $this->getUser();
        if ($user === null) {
            $this->addFlash('error', 'Пользователь не авторизован.');
            return $this->redirectToRoute('app_course_show', ['id' => $course->getId()], Response::HTTP_SEE_OTHER);
        }

        try {
            $response = $this->billingClient->payment(
                $user->getApiToken(),
                $course->getCode()
            );

            if (isset($response['code'])) {
                if (isset($response['errors']['course'])) {
                    $message = $response['errors']['course'];
                } elseif (isset($response['errors']['payment'])) {
                    $message = $response['errors']['payment'];
                } else {
                    $message = 'Произошла ошибка при оплате курса.';
                }
                $this->addFlash('error', $message);
            } else {
                $this->addFlash('success', 'Курс успешно оплачен');
            }

            $userResponse = $this->billingClient->getCurrentUser($user->getApiToken());
            if (isset($userResponse['balance'])) {
                $user->setBalance((float)$userResponse['balance']);
            }
        } catch (BillingUnavailableException | \Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_course_show', ['id' => $course->getId()], Response::HTTP_SEE_OTHER);
    }
}
