<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;
    
    protected $table = 'bookings';

    protected $fillable = [
        "product_id",
        "order_number",
        "first_name",
        "last_name",
        "phone_number",
        "email",
        "pickup_city",
        "pickup_address",
        "pickup_date_time",
        "return_city",
        "return_address",
        "return_date_time",
        "car_month",
        "car_monthly_price",
        "deposit_type",
        "deposit_selected_tab",
        "deposit_price",
        "total_days",
        "promo_code",
        "promo_discount",
        "pay_now_discount",
        "summary_total_amount",
        "summary_total_vat",
        "total_discount_incl_vat",
        "total_price",
        "extras",
        "booking_page_slug",
        "payment_type",
        "payment_status",
        "booking_status",
        "transaction_id",
        "accept_terms",
        "valid_driving_license",
        "driver_age_above",
        "card_payment",
        "partial_percentage",
        "partial_amount",
        "language"
    ];
}
