<?php
class CartManager {

    public static function isInCart($productId) {
        return isset($_SESSION['cart'][$productId]) && $_SESSION['cart'][$productId] > 0;
    }

    public static function getQuantityInCart($productId) {
        return $_SESSION['cart'][$productId] ?? 0;
    }

    public static function getCartProductIds() {
        if (empty($_SESSION['cart'])) {
            return [];
        }
        return array_keys($_SESSION['cart']);
    }

    public static function addToCart($productId, $quantity = 1) {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        if (isset($_SESSION['cart'][$productId])) {
            $_SESSION['cart'][$productId] += $quantity;
        } else {
            $_SESSION['cart'][$productId] = $quantity;
        }
    }

    public static function removeFromCart($productId) {
        if (isset($_SESSION['cart'][$productId])) {
            unset($_SESSION['cart'][$productId]);
        }
    }

    public static function getCartTotal() {
        $total = 0;
        if (!empty($_SESSION['cart'])) {
            foreach ($_SESSION['cart'] as $quantity) {
                $total += $quantity;
            }
        }
        return $total;
    }
}

