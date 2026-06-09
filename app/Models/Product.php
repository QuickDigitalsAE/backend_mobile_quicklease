<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class Product extends Model
{
    use HasFactory, LogsActivity;
    
    protected $table = 'products';

    protected $fillable = [
        "catalog_id",
        "additional_catalog_ids",
        "car_locations",
        "slug",
        "daily_price",
        "old_daily_price",
        "weekly_price",
        "old_weekly_price",
        "monthly_price",
        "old_monthly_price",
        "flexible_cars_monthly_prices",
        "personal_cars_monthly_prices",
        "monthly_installment_24_months",
        "monthly_installment_36_months",
        "installment_per_month",
        "installment_per_month_with_down",
        "installment_per_month_final_term",
        "down_payment",
        "security_deposit",
        "security_deposit_waiver_daily",
        "security_deposit_waiver_monthly",
        "vehicle_type",
        "main_image",
        "car_images",
        "specification_auto",
        "year",
        "model",
        "pay_now_discount",
        "featured",
        "promo_status",
        "stock_status",
        "product_status",
        "book_now_button",
        "show_on_home",
        "hidden_from_list",
        "show_documents",
        "created_by",
        "updated_by"
    ];
}
