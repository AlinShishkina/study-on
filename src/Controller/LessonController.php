<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\Lesson;
use App\Form\LessonType;
use App\Repository\LessonRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/lessons')]
final class LessonController extends AbstractController
{
    #[Route('/new/{course}', name: 'app_lesson_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, Course $course): Response
    {
        $lesson = new Lesson();
        $lesson->setCourse($course);

        $form = $this->createForm(LessonType::class, $lesson);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $entityManager->persist($lesson);
                $entityManager->flush();

                return $this->redirectToRoute('app_course_show', ['id' => $course->getId()], Response::HTTP_SEE_OTHER);
            } else {
                // Если форма невалидна, выводим ошибки на странице
                $this->addFlash('error', 'При валидации возникли ошибки.');
            }
        }

        return $this->render('lesson/new.html.twig', [
            'lesson' => $lesson,
            'course' => $course,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_lesson_show', methods: ['GET'])]
    public function show(Lesson $lesson): Response
    {
        return $this->render('lesson/show.html.twig', [
            'lesson' => $lesson,
            'course' => $lesson->getCourse(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_lesson_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Lesson $lesson, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(LessonType::class, $lesson);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_lesson_show', ['id' => $lesson->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('lesson/edit.html.twig', [
            'lesson' => $lesson,
            'course' => $lesson->getCourse(),
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_lesson_delete', methods: ['POST'])]
    public function delete(Request $request, Lesson $lesson, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$lesson->getId(), $request->request->get('_token'))) {
            $entityManager->remove($lesson);
            $entityManager->flush();

            // Можно добавить сообщение об успешном удалении
            $this->addFlash('success', 'Урок успешно удалён.');
        }

        return $this->redirectToRoute('app_course_show', ['id' => $lesson->getCourse()->getId()], Response::HTTP_SEE_OTHER);
    }
}
