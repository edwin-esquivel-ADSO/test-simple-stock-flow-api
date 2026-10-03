<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Domain\Model\Product;
use App\Domain\Model\Sale;
use App\Domain\Model\SaleItem;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\Quantity;
use App\Domain\ValueObject\Username;
use App\Domain\ValueObject\Role;
use App\Domain\Exception\InsufficientStockException;
use App\Domain\Exception\EmptySaleException;
use App\Domain\Exception\RepeatedProductException;
use App\Domain\Exception\InvalidPriceException;
use App\Domain\Exception\InvalidQuantityException;
use App\Domain\Exception\InvalidRoleException;
use DateTimeImmutable;

class DomainRulesTest extends TestCase
{
    public function test_money_precision_and_rounding(): void
    {
        $money = Money::of('12.505');
        // HALF_UP rounds ties away from zero -> 12.51
        $this->assertEquals('12.51', (string) $money->getAmount());
        $this->assertEquals('COP', $money->getCurrency());
    }

    public function test_negative_money_throws_exception(): void
    {
        $this->expectException(InvalidPriceException::class);
        Money::of('-10.00');
    }

    public function test_quantity_must_be_positive(): void
    {
        $this->expectException(InvalidQuantityException::class);
        new Quantity(0);
    }

    public function test_username_is_normalized_to_lowercase(): void
    {
        $user = new Username('  AdMiN_UsEr  ');
        $this->assertEquals('admin_user', $user->getValue());
    }

    public function test_role_validations(): void
    {
        $admin = new Role('admin');
        $this->assertTrue($admin->isAdmin());

        $seller = new Role('seller');
        $this->assertTrue($seller->isSeller());

        $this->expectException(InvalidRoleException::class);
        new Role('superadmin');
    }

    public function test_product_withdraw_exceeding_stock_fails(): void
    {
        $product = new Product(
            'prod-1',
            'Martillo',
            Money::of('25000.00'),
            5,
            'cat-1'
        );

        $this->expectException(InsufficientStockException::class);
        $product->withdraw(10);
    }

    public function test_sale_total_is_derived_dynamically(): void
    {
        $item1 = new SaleItem(
            'item-1',
            'sale-1',
            'prod-1',
            'Martillo',
            'Herramientas',
            new Quantity(2),
            Money::of('25000.00')
        );

        $item2 = new SaleItem(
            'item-2',
            'sale-1',
            'prod-2',
            'Tornillos',
            'Herramientas',
            new Quantity(5),
            Money::of('1000.00')
        );

        $sale = new Sale(
            'sale-1',
            new DateTimeImmutable(),
            new Username('vendedor'),
            'user-1',
            [$item1, $item2]
        );

        // 2 * 25000 + 5 * 1000 = 50000 + 5000 = 55000
        $this->assertEquals(55000.00, $sale->getTotal()->toFloat());
    }

    public function test_sale_rejects_empty_items(): void
    {
        $this->expectException(EmptySaleException::class);
        new Sale(
            'sale-1',
            new DateTimeImmutable(),
            new Username('vendedor'),
            'user-1',
            []
        );
    }

    public function test_sale_rejects_repeated_products(): void
    {
        $item1 = new SaleItem(
            'item-1',
            'sale-1',
            'prod-1',
            'Martillo',
            'Herramientas',
            new Quantity(1),
            Money::of('25000.00')
        );

        $item2 = new SaleItem(
            'item-2',
            'sale-1',
            'prod-1',
            'Martillo',
            'Herramientas',
            new Quantity(2),
            Money::of('25000.00')
        );

        $this->expectException(RepeatedProductException::class);
        new Sale(
            'sale-1',
            new DateTimeImmutable(),
            new Username('vendedor'),
            'user-1',
            [$item1, $item2]
        );
    }
}
