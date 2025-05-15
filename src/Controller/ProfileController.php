<?php

namespace App\Controller;

use App\Exception\BillingUnavailableException;
use App\Repository\CourseRepository;
use App\Service\BillingClient;
use JsonException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/profile')]
class ProfileController extends AbstractController
{
    public function __construct(
        private BillingClient $billingClient,
    ) {
    }
    
    #[IsGranted('ROLE_USER')]
    #[Route('/', name: 'app_profile')]
    public function index(): Response
    {
        $user = $this->getUser();

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'user_role' => in_array('ROLE_SUPER_ADMIN', $user->getRoles()) ? 'Администратор' : 'Пользователь'
        ]);
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/transactions', name: 'app_profile_transactions')]
    public function transactions(
        BillingClient $billingClient,
        CourseRepository $courseRepository
    ): Response {
        $user = $this->getUser();
        
        try {
            $transactions = $billingClient->transactions($user->getApiToken());

            // получаем курсы по коду
            $courseCodes = array_unique(array_column($transactions, 'course_code'));
            $courses = $courseRepository->findBy(['code' => $courseCodes]);

            $coursesByCode = [];
            foreach ($courses as $course) {
                $coursesByCode[$course->getCode()] = $course;
            }

            foreach ($transactions as &$transaction) {
                if (isset($transaction['course_code'])){
                    $courseCode = $transaction['course_code'];
                    if (isset($coursesByCode[$courseCode])) {
                        $transaction['course'] = $coursesByCode[$courseCode];
                    } else {
                        $transaction['course'] = null;
                    }
                } else {
                    $transaction['course'] = null;
                }
            }
        } catch (BillingUnavailableException | JsonException $e) {
            throw new \Exception('Произошла ошибка во время получения данных о транзакциях: '. $e->getMessage());
        }

        if (isset($response['code'])) {
            throw new BillingUnavailableException(
                'Ошибка получения данных о транзакциях. Пройдите авторизацию заново.'
            );
        }

        return $this->render('profile/transactions.html.twig', [
            'transactions' => $transactions,
        ]);
    }
}