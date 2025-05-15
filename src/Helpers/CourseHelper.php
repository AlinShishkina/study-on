<?php

namespace App\Helpers;

class CourseHelper
{
    public static $typeNames = [
        'free' => 'Бесплатный',
        'rent' => 'Аренда',
        'buy' => 'Платный'
    ];
    
    /**
     * Объединяет данные курсов с дополнительным ответом, добавляет тип и название типа.
     *
     * @param array $response Массив данных с ключом 'code' и информацией о типе и цене
     * @param array $courses Массив объектов курсов
     * @return array
     */
    public static function merge(array $response, array $courses): array
    {
        $result = [];
        
        // Создаем карту по коду курса из $response
        $responseMap = [];
        foreach ($response as $item) {
            if (!is_array($item) || !isset($item['code'])) {
                continue;
            }
            $responseMap[$item['code']] = $item;
        }
        
        foreach ($courses as $course) {
            // Проверяем, что объект имеет нужные методы
            if (!is_object($course) || !method_exists($course, 'getCode') || !method_exists($course, 'toArray')) {
                continue;
            }

            $code = $course->getCode();

            $courseArray = $course->toArray();
            if (!is_array($courseArray)) {
                // Если toArray вернул не массив - пропускаем
                continue;
            }

            // Начинаем с типа 'free' по умолчанию
            $item = array_merge($courseArray, ['type' => 'free']);

            // Если есть данные в $responseMap, обновляем тип и цену
            if (isset($responseMap[$code]) && is_array($responseMap[$code])) {
                $item['type'] = $responseMap[$code]['type'] ?? 'free';

                if (isset($responseMap[$code]['price'])) {
                    $item['price'] = $responseMap[$code]['price'];
                }
            }

            // Добавляем человекочитаемое название типа или 'Неизвестно'
            $item['type_name'] = self::$typeNames[$item['type']] ?? 'Неизвестно';

            $result[] = $item;
        }
        
        return $result;
    }

    /**
     * Добавляет к курсам информацию о транзакциях.
     *
     * @param array $courses Массив курсов (массивы или объекты с toArray)
     * @param array $transactions Массив транзакций с ключом 'course_code'
     * @return array
     */
    public static function addTransactions(array $courses, array $transactions): array
    {
        $result = [];

        // Создаем карту транзакций по коду курса
        $responseMap = [];
        foreach ($transactions as $item) {
            if (!is_array($item) || !isset($item['course_code'])) {
                continue;
            }
            $responseMap[$item['course_code']] = $item;
        }

        foreach ($courses as $course) {
            // Если объект, преобразуем в массив
            if (is_object($course) && method_exists($course, 'toArray')) {
                $course = $course->toArray();
            }

            if (!is_array($course) || !isset($course['code'])) {
                continue;
            }

            $code = $course['code'];

            $item = array_merge(
                $course,
                [
                    'transaction' => $responseMap[$code] ?? null
                ]
            );

            $result[] = $item;
        }

        return $result;
    }
}
