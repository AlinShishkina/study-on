<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\Lesson;
use App\Form\LessonType;
use App\Repository\CourseRepository;
use App\Repository\LessonRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/lessons')]
class LessonController extends AbstractController
{
    /**
     * Отображает список всех уроков
     */
    #[Route('/', name: 'app_lesson_index', methods: ['GET'])]
    public function index(LessonRepository $lessonRepository): Response
    {
        return $this->render('lesson/index.html.twig', [
            'lessons' => $lessonRepository->findAll(),
        ]);
    }

    /**
     * Создание нового урока, привязанного к курсу
     * Доступно только для пользователей с ролью SUPER_ADMIN
     */
    #[IsGranted('ROLE_SUPER_ADMIN')]
    #[Route('/new/{id}', name: 'app_lesson_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        int $id,
        CourseRepository $courseRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $course = $courseRepository->find($id);

        if (!$course) {
            throw $this->createNotFoundException('Курс не найден');
        }

        $lesson = new Lesson();
        $lesson->setCourse($course);

        $form = $this->createForm(LessonType::class, $lesson);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $entityManager->persist($lesson);
                $entityManager->flush();

                $this->addFlash('success', 'Урок успешно создан.');
                return $this->redirectToRoute('app_course_show', [
                    'id' => $course->getId()
                ], Response::HTTP_SEE_OTHER);
            } else {
                $this->addFlash('error', 'Форма заполнена некорректно.');
            }
        }

        return $this->render('lesson/new.html.twig', [
            'lesson' => $lesson,
            'form' => $form,
            'course' => $course,
        ]);
    }

    /**
     * Отображение одного урока
     * Доступно для пользователей с ролью USER и выше
     */
    #[IsGranted('ROLE_USER')]
    #[Route('/{id}', name: 'app_lesson_show', methods: ['GET'])]
    public function show(Lesson $lesson): Response
    {
        return $this->render('lesson/show.html.twig', [
            'lesson' => $lesson,
            'course' => $lesson->getCourse(),
        ]);
    }

    /**
     * Редактирование существующего урока
     * Доступно только для пользователей с ролью SUPER_ADMIN
     */
    #[IsGranted('ROLE_SUPER_ADMIN')]
    #[Route('/{id}/edit', name: 'app_lesson_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Lesson $lesson,
        EntityManagerInterface $entityManager
    ): Response {
        $form = $this->createForm(LessonType::class, $lesson);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $entityManager->flush();

                $this->addFlash('success', 'Урок успешно обновлён.');
                return $this->redirectToRoute('app_course_show', [
                    'id' => $lesson->getCourse()->getId()
                ], Response::HTTP_SEE_OTHER);
            } else {
                $this->addFlash('error', 'Форма заполнена некорректно.');
            }
        }

        return $this->render('lesson/edit.html.twig', [
            'lesson' => $lesson,
            'form' => $form,
            'course' => $lesson->getCourse(),
        ]);
    }

    /**
     * Удаление урока
     * Доступно только для пользователей с ролью SUPER_ADMIN
     */
    #[IsGranted('ROLE_SUPER_ADMIN')]
    #[Route('/{id}', name: 'app_lesson_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Lesson $lesson,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('delete'.$lesson->getId(), $request->request->get('_token'))) {
            $entityManager->remove($lesson);
            $entityManager->flush();

            $this->addFlash('success', 'Урок успешно удалён.');
        } else {
            $this->addFlash('error', 'CSRF токен недействителен.');
        }

        return $this->redirectToRoute('app_course_show', [
            'id' => $lesson->getCourse()->getId()
        ], Response::HTTP_SEE_OTHER);
    }
}
