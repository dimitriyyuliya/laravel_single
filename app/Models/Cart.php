<?php

namespace App\Models;

use App\Helpers\Obj;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Cart extends App
{
    protected $guarded = ['id', 'created_at', 'updated_at']; // Запрещается редактировать


    use SoftDeletes;


    /*

    // Массив корзины

    $cart = [

        // Товары в массиве индексированном без ключей
        'products' => [
            0 => [

                // Объект товара
                'id' => 1,
                'title' => 'Product1',
                'slug' => 'product',
                'price' => 200.50,
                'img' => '/img/product.jpg',

                // Добавляемые значения
                'qty' => 1,
                'message' => 'Message',
                'sum' => 250.50, // Сумма товара, кол-во и модификаторов
                'modifiers_sum' => 50, // Сумма модификаторов

                // Модификаторы
                'modifiers' => [
                    0 => [
                        'product_id' => 1,
                        'modifiers_groups_id' => 2,
                        'modifiers_groups_title' => 'Title',
                        'modifiers_elements_id' => 3,
                        'modifiers_elements_title' => 3,
                        'title' => 'Group - Element',
                        'price' => 50,
                    ],
                ],
            ],
        ],

        // Общее кол-во, сумма в корзине
        'qty' => 1,
        'sum' => 200.50,
    ];

    */


    /********************** Статичные публичные методы для работы с корзиной **********************/

    /**
     *
     * @return bool
     *
     * Метод добавляем в сессию товар и меняем общее кол-во и общую сумму в корзине.
     * $product - объект товара.
     * $qty - кол-во, по-умолчанию 1, необязательный параметр.
     * $modifiers - массив модификаторов, необязательный параметр.
     */
    public static function add($product, int $qty = 1, $modifiers = []) {
        if (!empty($product->id)) {

            // Добавляем модификаторы к товару
            if ($modifiers) {
                $product = self::modifiers($product, $modifiers);
            }

            // Добавляем к товару кол-во и сумму с модификаторами
            $product->sum = $product->price + ($product->modifiers_sum ?? 0);
            $product->qty = $qty;

            // Получаем кол-во в массиве корзины
            $nextKeyProduct = session()->has('cart.products') ? count(session()->get('cart.products')) : 0;

            // Сохраняем в сессию товар
            session()->put("cart.products.{$nextKeyProduct}", $product);

            // Добавим в корзину сумму и кол-во
            self::cartPlus($product->sum, $qty);
            return true;
        }
        return false;
    }


    /**
     *
     * @return bool
     *
     * Метод добавляем в сессию товар(ы) и меняем общее кол-во и общую сумму в корзине.
     * $keyProductInCart - ключ товара в массиве корзины.
     * $qty - кол-во, по-умолчанию 1, необязательный параметр.
     */
    public static function plus(int $keyProductInCart, int $qty = 1)
    {
        if (session()->has("cart.products.{$keyProductInCart}")) {
            $product = session()->get("cart.products.{$keyProductInCart}");

            // Сохраняем общие сумму и кол-во в корзине и пересчитываем
            self::cartPlus($product->sum, $qty);

            // Увеличиваем кол-во товара
            $product->qty = $product->qty + $qty;

            // Сохраняем в сессию
            session()->put("cart.products.{$keyProductInCart}", $product);

            return true;
        }
        return false;
    }


    /**
     *
     * @return bool
     *
     * Метод минусуем товар(ы) и меняем общее кол-во и общую сумму в корзине.
     * $keyProductInCart - ключ товара в массиве корзины.
     * $qty - кол-во, по-умолчанию 1, необязательный параметр.
     */
    public static function minus(int $keyProductInCart, int $qty = 1)
    {
        if (session()->has("cart.products.{$keyProductInCart}")) {
            $product = session()->get("cart.products.{$keyProductInCart}");

            // Сохраняем общие сумму и кол-во в корзине и пересчитываем
            self::cartMinus($product->sum, $qty);

            // Уменьшаем кол-во товара
            $product->qty = $product->qty - $qty;

            // Если передаваемое кол-во товара меньше имеющегося, то удаляем и пересчитываем корзину
            if ($product->qty <= 0) {
                self::destroy($product);
                return true;
            }

            // Сохраняем в сессию
            session()->put("cart.products.{$keyProductInCart}", $product);

            return true;
        }
        return false;
    }


    /**
     *
     * @return bool
     *
     * Метод удаляем товар(ы) и меняем общее кол-во и общую сумму в корзине.
     * $keyProductInCart - ключ товара в массиве корзины.
     */
    public static function destroy($keyProductInCart)
    {
        $keyProductInCart = (int)$keyProductInCart;
        if (session()->has("cart.products.{$keyProductInCart}")) {

            // Если в сессии cart.products больше одно элемента, то удаляем элемент
            if (count(session()->get('cart.products')) > 1) {

                $product = session()->get("cart.products.{$keyProductInCart}");

                // Сохраняем общие сумму и кол-во в корзине и пересчитываем
                self::cartMinus($product->sum, $product->qty);

                // Удаляем из сессии товар(ы)
                session()->forget("cart.products.{$keyProductInCart}");

            // Если в сессии cart один элемент, то удаляем всю сессию cart
            } else {
                session()->forget('cart');
            }

            return true;
        }
        return false;
    }



    /********************** Служебные методы для работы с корзиной **********************/

    /**
     *
     * @return bool
     *
     * Прибавляет сумму к сумме корзины, возвращает true или false.
     * Кол-во в корзине также меняем.
     *
     * $sumPlus - сумма прибавляемая в корзину (сумма товара и модификаторов).
     * $qty - кол-во.
     */
    private static function cartPlus(float $sumPlus,int $qty) {

        $cartSum = session()->has('cart.sum') ? session()->get('cart.sum') : 0;
        $cartQty = session()->has('cart.qty') ? session()->get('cart.qty') : 0;

        if ($qty > 0) {

            $sum = $cartSum + ($sumPlus * $qty);
            $qty = $cartQty + $qty;

            // Сохраним в сессию новые значения
            session()->put('cart.sum', $sum);
            session()->put('cart.qty', $qty);

            return true;
        }
        return false;
    }


    /**
     *
     * @return bool
     *
     * Вычетает из суммы в корзине передаваемую сумму, возвращает true или false.
     * Кол-во в корзине также меняем.
     * $sumMinus - сумма вычитаемая из корзины (сумма товара и модификаторов).
     * $qty - кол-во.
     */
    private static function cartMinus(float $sumMinus,int $qty) {

        $cartSum = session()->has('cart.sum') ? session()->get('cart.sum') : 0;
        $cartQty = session()->has('cart.qty') ? session()->get('cart.qty') : 0;

        if ($cartQty > 0 && $qty > 0) {
            $sum = $cartSum - ($sumMinus * $qty);
            $qty = $cartQty - $qty;

            // Сохраним в сессию новые значения
            session()->put('cart.sum', $sum);
            session()->put('cart.qty', $qty);
            return true;
        }
        return false;
    }


    /**
     *
     * @return object
     *
     * Возвращает объект товара с модификаторами.
     * Метод добавляет к сессии товара, модификаторы переданные в массиве.
     * Считает сумму товара и модификаторов.
     * $product - объект товара.
     * $modifiers - массив модификаторов.
     */
    private static function modifiers($product, $modifiers)
    {
        if ($modifiers) {

            // Получаем все модификаторы
            $modifiersAll = Obj::getBelong(ModifierGroup::class, 'modifiers');

            $modifiersArr = [];
            $sum = 0;
            foreach ($modifiers as $field => $modifier) {

                // Разбиваем значение на группы и элемент
                $groupId = Str::before($modifier, '_');
                $elementId = Str::after($modifier, '_');

                // Формируем данные модификаторов
                if (!empty($modifiersAll[$groupId])) {
                    $group = $modifiersAll[$groupId];
                    $element = $group->modifiers->find($elementId);
                    $price = $element->price ? ' (' . priceFormat($element->price) . ')' : null;

                    $modifiersArr[] = [
                        'product_id' => $product->id,
                        'groups_id' => $group->id,
                        'groups_title' => $group->title,
                        'elements_id' => $element->id,
                        'elements_title' => $element->title,
                        'title' => "{$group->title} - {$element->title}{$price}",
                        'price' => (float)$element->price,
                    ];

                    // Считаем общую сумму модификаторов
                    $sum += (float)$element->price;
                }
            }

            // Массив с модификаторами
            $product->modifiers = $modifiersArr;

            // Сумма модификаторов
            $product->modifiers_sum = $sum;
        }
        return $product;
    }



    /********************** Вспомогательные статичные методы для работы с корзиной **********************/

    /**
     *
     * @return string
     *
     * Возвращает комментарий и модификаторы для товара в html разметке.
     * $productFromCart - объект товара из корзины.
     */
    public static function getModifiers($productFromCart)
    {
        $html = '';
        if ($productFromCart) {

            // Комментарий пользователя
            $message = $productFromCart->message ?? null;
            $html .= $message ? "<div>{$message}</div>\n" : '';

            // Модификаторы
            $modifiers = $productFromCart->modifiers ?? null;
            if ($modifiers) {
                foreach ($modifiers as $modifier) {
                    $html .= "<div>{$modifier['title']}</div>\n<hr class='hr_black'>";
                }
            }
        }
        return $html;
    }
}