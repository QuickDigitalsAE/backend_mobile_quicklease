<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApisFiles\UserController;
use App\Http\Controllers\ApisFiles\RolePermissionController;
use App\Http\Controllers\ApisFiles\MenuController;
use App\Http\Controllers\ApisFiles\WebContentController;
use App\Http\Controllers\ApisFiles\MobileGuideController;
use App\Http\Controllers\ApisFiles\GoogleReviewController;
use App\Http\Controllers\ApisFiles\SidebarBannerController;
use App\Http\Controllers\ApisFiles\PartnerController;
use App\Http\Controllers\ApisFiles\BlogController;
use App\Http\Controllers\ApisFiles\BookingController;
use App\Http\Controllers\ApisFiles\PromotionController;
use App\Http\Controllers\ApisFiles\PromoCodeController;
use App\Http\Controllers\ApisFiles\TestimonialController;
use App\Http\Controllers\ApisFiles\EnquiryController;
use App\Http\Controllers\ApisFiles\RequestFormController;
use App\Http\Controllers\ApisFiles\CatalogController;
use App\Http\Controllers\ApisFiles\UploadImageController;
use App\Http\Controllers\ApisFiles\OurLocationsController;
use App\Http\Controllers\ApisFiles\ProductsController;
use App\Http\Controllers\ApisFiles\ProductCoveragesController;
use App\Http\Controllers\ApisFiles\ProductPropertiesController;
use App\Http\Controllers\ApisFiles\PaymentController;
use App\Http\Controllers\ApisFiles\SubscriptionController;
use App\Http\Controllers\ApisFiles\UserActivityLogController;
use App\Http\Controllers\ApisFiles\CustomerAuthController;
use App\Http\Controllers\ApisFiles\NotificationController;
use App\Http\Controllers\ApisFiles\SettingController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

//  Route::get('/clear-cache', function() {
//     $exitCode = Artisan::call('cache:clear');
//     $exitCode = Artisan::call('route:clear');
//     $exitCode = Artisan::call('view:clear');
//     $exitCode = Artisan::call('config:clear');
    
//     return response()->json([
//         'status' => false,
//         'message' => "Artisan executed sucessfully!"
//     ], 200);
// });

// Define your login API route
Route::post('login', [UserController::class, 'login']);

Route::get('/fetchRss/{lang?}/{per_page_count?}', [SettingController::class,'fetchRssFeeds']);

Route::get('/fetch-sitemap/{lang?}', [SettingController::class, 'fetchSitemap']);


Route::get('our-locations/{lang?}', [OurLocationsController::class,'frontendLocationsList']);

// Get catalog meta data 
Route::get('/fetchCatalogMeta/{lang}/{slug}', [CatalogController::class, 'fetchCatalogMetaData'])
    ->where('slug', '.*'); 

// Get all car list related to the catalogs
Route::get('/fetchContent/{lang}/{slug}', [CatalogController::class, 'fetchFrontendContent'])
    ->where('slug', '.*'); 

// Get flexible rentals single details page api
Route::get('/flexible-rentals/{lang}', [CatalogController::class, 'flexibleFrontendContent']);

// WebContents Get Data Apis
Route::group(['prefix' => 'webContents', 'middleware' => 'validateLang'], function() {
    
    Route::get('/combineContent/{lang}', [WebContentController::class,'combineContent']);
    
    /* Home content controller */
    Route::get('/fetchHomePageContent/{lang?}', [WebContentController::class, 'fetchHomePageContent']);
    
    // Fetch Faqs list for frontend side
    Route::get('/fetchFaqsContent/{lang?}/{per_page_count?}', [WebContentController::class, 'fetchFaqsList']);
    
    // Fetch About Us Content
    Route::get('/fetchAboutUs/{lang?}', [WebContentController::class, 'fetchAboutUs']);

    // Fetch Partners Content
    Route::get('/fetchPartners/{lang?}', [WebContentController::class, 'fetchPartners']);
    
    // Fetch Corporate Content
    Route::get('/fetchCorporateLease/{lang?}', [WebContentController::class, 'fetchCorporateLease']);
    
    // Fetch Lease To Own Content
    Route::get('/fetchLeaseToOwnContent/{lang?}', [WebContentController::class, 'fetchLeaseToOwnContent']);
    
    // Fetch Contact Us Content
    Route::get('/fetchContactUs/{lang?}', [WebContentController::class, 'getWebContactUs']);
    
    // Fetch Testimonials Content
    Route::get('/fetchVideoTestimonial/{lang?}', [WebContentController::class, 'getWebVideoTestimonials']);
    
    
});

// Blogs Get Data Apis
Route::group(['prefix' => 'blogs', 'middleware' => 'validateLang'], function(){
    //Fetched all blogs
    Route::get('/frontendList/{lang?}/{per_page_count?}', [BlogController::class, 'blogsFrontendList']);
    Route::get('/fetchDetail/{slug}/{lang?}', [BlogController::class, 'blogFetchDetail']);
});

// Partners Get Data Apis
Route::group(['prefix' => 'partners', 'middleware' => 'validateLang'], function(){
    //Fetched all Partners
    Route::get('/frontendList/{lang?}', [PartnerController::class, 'partnersFrontendList']);
    Route::get('/fetchDetail/{slug}/{lang?}', [PartnerController::class, 'partnerFetchDetail']);
});


// Partners Get Data Apis
Route::group(['prefix' => 'products', 'middleware' => 'validateLang'], function(){
    
    // Filter products 
    Route::post('/apply-filter/{slug}/{lang?}/{per_page?}', [ProductsController::class, 'applyProductFilter']);
    
    //Fetched all Cars
    Route::get('/allCars/{lang?}/{home_page?}/{promotion_list?}', [ProductsController::class, 'frontendProductsList']);
    // Route::get('/allCarsDropdownList/{lang?}/{carids?}', [ProductsController::class, 'allCarsDropdownList']);
    
    // Fetched all Locations
    Route::get('/carsLocations/{lang?}', [ProductsController::class, 'carsLocations']);
    
    
    // Get detail of the car by slug 
    Route::get('/detail/{lang}/{slug}', [ProductsController::class, 'productFetchDetail'])
    ->where('slug', '.*');
    
    // Get all cars related to the catalogs
    Route::get('/fetchCatalogCars/{slug}/{lang?}', [ProductsController::class, 'fetchCatalogCars']);
});

// Prodcut properties Get Data Apis
Route::group(['prefix' => 'properties', 'middleware' => 'validateLang'], function(){
    //Fetched all properties
    Route::get('/frontendList/{lang?}', [ProductPropertiesController::class, 'propertiesFrontendList']);
});

// Prodcut Booking Apis
Route::group(['prefix' => 'bookings', 'middleware' => 'validateLang'], function(){
    
    //Fetched all coverages
    Route::post('/coveragesList/{id?}/{lang?}', [BookingController::class, 'coveragesListForBooking']);
    
    //Fetched all coverages
    Route::post('/create/{lang?}', [BookingController::class, 'bookingStore']);
    
});

// Route::post('/payment/initiate', [PaymentController::class, 'initiatePayment']);

Route::post('/etisalatPayment/{lang?}', [PaymentController::class, 'executeEtisalatPayment']);
Route::get('/callback/{lang?}/{bookingId?}', [PaymentController::class, 'paymentCallback']);


// Promotion Get Data Apis
Route::group(['prefix' => 'promotions', 'middleware' => 'validateLang'], function(){
    
    // Fetched all list
    Route::get('/frontendList/{lang?}/{per_page_count?}', [PromotionController::class, 'frontendList']);
    
    // Promotions list for promo code section
    Route::get('/listForPromo/{lang?}', [PromotionController::class, 'frontendListForPromo']);
    
    // Get In Touch form Calculator
    Route::post('/formCalculator/{id}/{lang?}', [PromotionController::class, 'promotionsFormCalculator']);
    
    // Get single detail page
    Route::get('/fetchDetail/{slug?}/{lang?}', [PromotionController::class, 'fetchDetail']);
    
});

// Get single detail
Route::get('/otherPromotion/{slug?}/{lang?}', [PromotionController::class, 'fetchDetail']);
    
// Product Form Calculator Data Apis
Route::group(['prefix' => 'products', 'middleware' => 'validateLang'], function(){
    
    // Booking form Calculator
    Route::post('/formCalculator/{id}/{lang?}', [ProductsController::class, 'productsFormCalculator']);
    
    // Search Engine With Calculator
    Route::get('/searchEngine/{lang?}/{per_page_count?}', [BookingController::class, 'productSearchEngine']);
});

// Testimonials Get Data Apis
Route::group(['prefix' => 'testimonials', 'middleware' => 'validateLang'], function(){
    //Fetched all blogs
    Route::get('/frontendList/{lang?}/{per_page_count?}', [TestimonialController::class, 'frontendTestimonialsList']);
});

// For Enquiry Form
Route::group(['prefix' => 'enquiries', 'middleware' => 'validateLang'], function(){
    Route::post('/offer_form/{lang?}', [EnquiryController::class, 'offerForm']);
    Route::post('/request_form/', [EnquiryController::class, 'RequestForm']);
});

Route::group(['prefix' => 'quicklease', 'middleware' => 'validateLang'], function(){
    Route::post('/request_form', [RequestFormController::class, 'requestForm']);
});

// For Contact Us Form route
Route::post('/contact-us-form', [EnquiryController::class, 'contactusForm']);

// Upload Image Section
Route::post('/innerPages/uploadImage/', [UploadImageController::class, 'uploadSingleImage']);

// Frontend lease a review form post
Route::post('/testimonials/leave_review/{lang?}', [TestimonialController::class, 'storeFrontendTestimonial']);


// Subscription Email APIs
Route::group(['prefix' => 'subscription'], function(){
    Route::post('/new_subscription', [SubscriptionController::class, 'newSubscription']);
});


// Web Content Resource
Route::group(['prefix' => 'webContents', 'middleware' => 'validateLang'], function() {
    // Fetch meta title and description 
    Route::get('/fetchMeta/{slug}/{lang?}', [WebContentController::class, 'getWebMeta']);

    // Fetch full data of the pages
    Route::get('/fetchMetaData/{slug}/{lang?}', [WebContentController::class, 'getWebMetaDeta']);
});

Route::get('/fetchRandomBanner/{lang?}', [SidebarBannerController::class, 'getRadomBanner']);

Route::middleware(['auth:sanctum'])->group(function () {
    
    // Define your registration API route
    Route::post('register', [UserController::class, 'register']);
    
    // User active/inactive 
    Route::get('/userActivate/{id}/{status}', [UserController::class, 'userStatus']);

    // Get a list of all users
    Route::get('/allUsers', [UserController::class, 'listUsers']);

    // Get a user profile data
    Route::get('/getUserProfile', [UserController::class, 'getProfile']);

    // Get a specific role with permissions by role ID
    Route::get('/userEdit/{id?}', [UserController::class, 'editUser']);

    // Update a user by ID
    Route::post('/userUpdate/{id}', [UserController::class, 'updateUser']);

    // Delete a user by ID
    Route::delete('/userDelete/{id}', [UserController::class, 'deleteUser']);
    
    Route::group(['prefix' => 'activities', 'middleware' => 'validateLang'], function(){
        
        // Get all user activity logs on the admin side
        Route::get('/adminSideAllLogs/{per_page_count?}', [UserActivityLogController::class, 'adminSideAllLogs']);
        
        // Get all user auth activities logs
        Route::get('/authHistory/{per_page_count?}', [UserActivityLogController::class, 'userAuthHistory']);
    });
    
    Route::group(['prefix' => 'quicklease', 'middleware' => 'validateLang'], function(){
        Route::get('/form_list/{per_page_count?}', [RequestFormController::class, 'requestFormList']);
        
        // Add Comment of the team member 
        Route::post('/contractUpdate/{id}', [RequestFormController::class, 'contractUpdate']);
    });

    // User Logout
    Route::post('logout', [UserController::class, 'logout']);
    
    // Define a route to roles
    Route::group(['prefix' => 'roles'], function() {  
        // Get all roles
        Route::get('/', [RolePermissionController::class, 'getAllRoles']);

        // Get all permissions
        Route::get('/allPermissions', [RolePermissionController::class, 'getAllPermissions']);

        // Create a role with permissions
        Route::post('/create', [RolePermissionController::class, 'createRoleWithPermissions']);

        // Get a specific role with permissions by role ID
        Route::get('/edit/{roleId}', [RolePermissionController::class, 'editRoleWithPermissions']);

        // Update a specific role with permissions by role ID
        Route::post('/update/{roleId}', [RolePermissionController::class, 'updateRoleWithPermissions']);

        //Delete role with permissions by role ID
        Route::delete('/remove/{roleId}', [RolePermissionController::class, 'deleteRole']);
        
        // Create Permissions
        Route::post('/add-permissions', [RolePermissionController::class, 'addPermissions']);
        
        //Delete Group with inner permission
        Route::post('/delete-permission-group', [RolePermissionController::class, 'deletePermissionGroup']);

    });
    
    // Subscription Email APIs
    Route::group(['prefix' => 'subscription'], function(){
        Route::get('/all', [SubscriptionController::class, 'all']);
        Route::get('/detail/{id}', [SubscriptionController::class, 'subsriptionDetails']);
        Route::post('/update/{id}', [SubscriptionController::class, 'updateSubStatus']);
        Route::post('/delete/{id}', [SubscriptionController::class, 'deleteSubsription']);
    });

    // For partners section
    Route::group(['prefix' => 'partners', 'middleware' => 'validateLang'], function(){
        Route::get('/list/{lang?}/{per_page_count?}', [PartnerController::class, 'partnersList']);
        Route::post('/create/{lang?}', [PartnerController::class, 'storePartner']);
        Route::post('/update/{id}/{lang?}', [PartnerController::class, 'updatePartner']);
        Route::get('/edit/{id}/{lang?}', [PartnerController::class, 'partnerSingleDetail']);
        Route::post('/search_partner_list/{lang?}/{per_page_count?}', [PartnerController::class,'searchPartnersList']);
        Route::delete('/delete/{id}', [PartnerController::class, 'deletePartner']);
    });
    
    // For blogs section
    Route::group(['prefix' => 'blogs', 'middleware' => 'validateLang'], function(){
        Route::get('/list/{lang?}/{per_page_count?}', [BlogController::class, 'blogsList']);
        Route::post('/create/{lang?}', [BlogController::class, 'storeBlog']);
        Route::post('/update/{id}/{lang?}', [BlogController::class, 'updateBlog']);
        Route::get('/edit/{id}/{lang?}', [BlogController::class, 'blogSingleDetail']);
        Route::post('/search_blogs_list/{lang?}/{per_page_count?}', [BlogController::class,'searchBlogsList']);
        Route::delete('/delete/{id}', [BlogController::class, 'deleteBlog']);
    });
    
    // For Promotions section
    Route::group(['prefix' => 'promotions', 'middleware' => 'validateLang'], function(){
        Route::get('/list/{lang?}/{per_page_count?}', [PromotionController::class, 'promotionsList']);
        Route::post('/create/{lang?}', [PromotionController::class, 'storePromotion']);
        Route::post('/update/{id}/{lang?}', [PromotionController::class, 'updatePromotion']);
        Route::get('/edit/{id?}/{lang?}', [PromotionController::class, 'promotionSingleDetail']);
        Route::post('/search_promotions_list/{lang?}/{per_page_count?}', [PromotionController::class,'searchPromotionsList']);
        Route::delete('/delete/{id}', [PromotionController::class, 'deletePromotion']);
    });
    
    // For Promo Codes section
    Route::group(['prefix' => 'promo_codes', 'middleware' => 'validateLang'], function(){
        Route::get('/list/{lang?}/{per_page_count?}', [PromoCodeController::class, 'promoCodesList']);
        Route::post('/create/{lang?}', [PromoCodeController::class, 'storePromoCode']);
        Route::post('/update/{id}/{lang?}', [PromoCodeController::class, 'updatePromoCode']);
        Route::get('/edit/{id?}/{lang?}', [PromoCodeController::class, 'promoCodeSingleDetail']);
        Route::post('/search_list/{lang?}/{per_page_count?}', [PromoCodeController::class,'searchPromoCodesList']);
        Route::delete('/delete/{id}', [PromoCodeController::class, 'deletePromoCode']);
    });
    
    // For Testimonial section
    Route::group(['prefix' => 'testimonials', 'middleware' => 'validateLang'], function(){
        Route::get('/list/{lang?}/{per_page_count?}', [TestimonialController::class, 'testimonialsList']);
        Route::post('/create/{lang?}', [TestimonialController::class, 'storeTestimonial']);
        Route::post('/update/{id}/{lang?}', [TestimonialController::class, 'updateTestimonial']);
        Route::get('/edit/{id}/{lang?}', [TestimonialController::class, 'editTestimonial']);
        Route::post('/search_testimonials_list/{lang?}/{per_page_count?}', [TestimonialController::class,'searchTestimonialsList']);
        Route::delete('/delete/{id}', [TestimonialController::class, 'deleteTestimonial']);
    });
    
    
    // For Enquiry Form
    Route::group(['prefix' => 'enquiries', 'middleware' => 'validateLang'], function(){
        Route::get('/list/{form_type?}/{lang?}/{per_page_count?}', [EnquiryController::class, 'enquiriesList']);
        Route::post('/update_form/{id?}/{lang?}', [EnquiryController::class, 'updateEnquiry']);
        Route::get('/edit/{id?}/{lang?}', [EnquiryController::class, 'enquriySingleDetail']);
        Route::post('/search_enquiries_list/{lang?}/{per_page_count?}', [EnquiryController::class,'searchEnquiriesList']);
        Route::delete('/delete/{id}', [EnquiryController::class, 'deleteEnquiry']);
    });
    
    
    // For Catalog section
    Route::group(['prefix' => 'catalogs', 'middleware' => 'validateLang'], function(){
        Route::get('/list/{lang}/{per_page_count?}/{type?}', [CatalogController::class, 'catalogsList']);
        Route::get('/dropdownList/{lang?}/', [CatalogController::class, 'catalogsDropdownList']);
        Route::post('/create/{lang?}', [CatalogController::class, 'storeCatalog']);
        Route::post('/update/{id}/{lang?}', [CatalogController::class, 'updateCatalog']);
        Route::get('/edit/{id}/{lang?}', [CatalogController::class, 'catalogSingleDetail']);
        Route::delete('/delete/{id}', [CatalogController::class, 'deleteCatalog']);
        Route::post('/search_catalogs_list/{lang?}/{per_page_count?}', [CatalogController::class,'searchCatalogsList']);
    });
    
    
    // For products section
    Route::group(['prefix' => 'products', 'middleware' => 'validateLang'], function(){
        Route::get('/list/{lang?}/{per_page_count?}', [ProductsController::class, 'productsList']);
        Route::post('/create/{lang?}', [ProductsController::class, 'storeProduct']);
        Route::post('/update/{id}/{lang?}', [ProductsController::class, 'updateProduct']);
        Route::get('/edit/{id}/{lang?}', [ProductsController::class, 'productSingleDetail']);
        Route::post('/search_products_list/{lang?}/{per_page_count?}', [ProductsController::class,'searchProductsList']);
        Route::delete('/delete/{id}', [ProductsController::class, 'deleteProduct']);
    });
    
    // For Products Coverages
    Route::group(['prefix' => 'coverages', 'middleware' => 'validateLang'], function(){
        Route::get('/list/{lang?}/{per_page_count?}', [ProductCoveragesController::class, 'coveragesList']);
        Route::get('/dropdownList/{lang?}/', [ProductCoveragesController::class, 'coveragesListForProduct']);
        Route::post('/create/{lang?}', [ProductCoveragesController::class, 'storeCoverage']);
        Route::post('/update/{id}/{lang?}', [ProductCoveragesController::class, 'updateCoverage']);
        Route::get('/edit/{id}/{lang?}', [ProductCoveragesController::class, 'coverageSingleDetail']);
        Route::post('/search_list/{lang?}/{per_page_count?}', [ProductCoveragesController::class,'searchCoveragesList']);
        Route::delete('/delete/{id}', [ProductCoveragesController::class, 'deleteCoverage']);
    });
    
    // For products section
    Route::group(['prefix' => 'properties', 'middleware' => 'validateLang'], function(){
        Route::get('/list/{lang?}/{per_page_count?}', [ProductPropertiesController::class, 'propertiesList']);
        Route::post('/create/{lang?}', [ProductPropertiesController::class, 'storeProperty']);
        Route::post('/update/{id}/{lang?}', [ProductPropertiesController::class, 'updateProperty']);
        Route::get('/edit/{id}/{lang?}', [ProductPropertiesController::class, 'propertySingleDetail']);
        Route::post('/search_properties_list/{lang?}/{per_page_count?}', [ProductPropertiesController::class,'searchPropertiesList']);
        Route::delete('/delete/{id}', [ProductPropertiesController::class, 'deleteProperty']);
    });

    // For Google reviews
    Route::get('/googleReview/{id?}', [GoogleReviewController::class, 'getReview']);
    Route::post('/googleReview', [GoogleReviewController::class,'postReview']);
    Route::post('/googleReview/{id}', [GoogleReviewController::class, 'patchReview']);
    Route::delete('/googleReview/{id}', [GoogleReviewController::class, 'deleteReview']);
    
    // For Blogs dynamic sidebar banners
    Route::group(['prefix' => 'sidebarBanner', 'middleware' => 'validateLang'], function(){
        Route::get('/{lang?}/{id?}', [SidebarBannerController::class, 'getSideBanner']);
        Route::post('/{lang?}', [SidebarBannerController::class,'postSideBanner']);
        Route::post('/{lang?}/{id}', [SidebarBannerController::class, 'updateSideBanner']);
        Route::delete('/{id}', [SidebarBannerController::class, 'deleteSideBanner']);
    });

    // Prodcut Booking Apis
    Route::group(['prefix' => 'bookings', 'middleware' => 'validateLang'], function(){
        // Booking List by Status
        Route::post('/bookingList/{lang?}/{per_page_count?}', [BookingController::class, 'bookingList']);
        
        // Booking Search List by name,email and status
        Route::post('/search_bookingList/{lang?}/{per_page_count?}', [BookingController::class,'searchBookingList']);
        
        // Booking Status
        Route::post('/bookingStatus/{id}/{lang?}', [BookingController::class, 'bookingStatus']);
    });

    // Web Content Resource
    Route::group(['prefix' => 'webContents', 'middleware' => 'validateLang'], function() {
        
        //Home content
        Route::post('/home/{lang?}', [WebContentController::class,'createOrUpdateWebHome']);
        Route::get('/home/{lang?}', [WebContentController::class,'getWebHomeContent']);

        
        // Contact us content
        Route::post('/contact/{lang}', [WebContentController::class,'createOrUpdateWebContact']);
        
        // Elements content
        Route::post('/elements/{lang}', [WebContentController::class,'createOrUpdateElements']);
        Route::get('/elements/{lang?}', [WebContentController::class, 'getWebElements']);
        
        // About Us Routes
        Route::post('/aboutus/{lang?}', [WebContentController::class, 'createOrUpdateWebAboutUs']);
        Route::get('/aboutus/{lang?}', [WebContentController::class, 'getWebAboutUsContent']);

        // Partners Routes
        Route::post('/partner/{lang?}', [WebContentController::class, 'createOrUpdateWebPartner']);
        Route::get('/partner/{lang?}', [WebContentController::class, 'getWebPartnerContent']);
        
        /* Meta Data content controller */
        Route::post('/metadata/{slug}/{lang?}', [WebContentController::class, 'createUpdateMetaData']);
        Route::get('/metadata/{slug}/{lang?}', [WebContentController::class, 'getWebMetaDeta']);
        
        /* FAQs content controller */
        Route::post('/faqs/{lang?}', [WebContentController::class, 'createUpdateFaqs']);
        Route::get('/faqs/{lang?}', [WebContentController::class, 'getWebFaqs']);
        
        /* FAQs content controller */
        Route::post('/contact-us/{lang?}', [WebContentController::class, 'createUpdateContactUs']);
        Route::get('/contact-us/{lang?}', [WebContentController::class, 'getWebContactUs']);
        
        /* Testimonials videos controller */
        Route::post('/video-testimonial/{lang?}', [WebContentController::class, 'createUpdateVideoTestimonials']);
        Route::get('/video-testimonial/{lang?}', [WebContentController::class, 'getWebVideoTestimonials']);
        
        // Corporate Lease Routes
        Route::post('/corporate-lease/{lang?}', [WebContentController::class, 'createOrUpdateWebCorporateLease']);
        Route::get('/corporate-lease/{lang?}', [WebContentController::class, 'getWebCorporateLeaseContent']);
        
        // Lease To Own Routes
        Route::post('/lease-to-own-page/{lang?}', [WebContentController::class, 'createOrUpdateWebLeaseToOwn']);
        Route::get('/lease-to-own-page/{lang?}', [WebContentController::class, 'getWebLeaseToOwnContent']);
        
        
        // Product Inner Page Content Routes
        Route::post('/product-inner/{lang?}', [WebContentController::class, 'createOrUpdateWebProductInner']);
        Route::get('/product-inner/{lang?}', [WebContentController::class, 'getWebProductInnerContent']);
        
        // Section remove for all webContent api
        Route::post('section/remove-inner-object/{lang?}', [WebContentController::class, 'removeInnerObject']);
        
        // flexible-rentals Routes
        Route::post('/flexible-rentals/{lang?}', [WebContentController::class, 'createOrUpdateWebFlexibleRental']);
        Route::get('/flexible-rentals/{lang?}', [WebContentController::class, 'getWebFlexibleRental']);
    });

    Route::prefix('admin/notifications')->group(function () {

        Route::post('/', [NotificationController::class, 'store']);
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/all', [NotificationController::class, 'all']);
        Route::get('/{id}', [NotificationController::class, 'show']);
        Route::put('/{id}', [NotificationController::class, 'update']);
        Route::delete('/{id}', [NotificationController::class, 'destroy']);
        Route::patch('/{id}/revoke', [NotificationController::class, 'revoke']);
    });
});
// Subscription footer section api
Route::post('/subscription_email/', [WebContentController::class, 'subscriptionEmail']);

// For Mobile App APIs Endpoints
Route::group(['prefix' => 'mobile'], function () {
    
    Route::prefix('customer')->group(function () {

        // Public Routes
        Route::post('register', [CustomerAuthController::class, 'register']);
        Route::post('login', [CustomerAuthController::class, 'login']);
        Route::post('login-via-otp', [CustomerAuthController::class, 'loginViaOTP']);
        Route::post('forgot-password', [CustomerAuthController::class, 'forgotPassword']);
        Route::post('reset-password', [CustomerAuthController::class, 'resetPassword']);
    
        // Protected Routes
        Route::middleware('auth:customer')->group(function () {
            Route::post('change-password', [CustomerAuthController::class, 'changePassword']);
            Route::get('my-profile', [CustomerAuthController::class, 'getProfile']);
            Route::put('update-profile', [CustomerAuthController::class, 'updateProfile']);
            
            Route::delete('delete-account', [CustomerAuthController::class, 'deleteAccount']);
            Route::post('logout', [CustomerAuthController::class, 'logout']);
            
            // Booking List by Status
            Route::post('/bookingList/{lang?}/{per_page_count?}', [BookingController::class, 'customerBookingList']);
            
            Route::get('/allInquiries/{lang?}/{per_page_count?}', [EnquiryController::class, 'customerEnquiriesList']);

            // Frontend lease a review form post
            Route::post('/leave_review/{lang?}', [TestimonialController::class, 'storeFrontendTestimonial']);
            Route::get('/reviews/{lang?}/{per_page_count?}', [TestimonialController::class,'frontendTestimonialsList']);
        });
    
    });
    
    // mobile guidance apis
    Route::get('/guide/{id?}', [MobileGuideController::class, 'getGuide']);
    Route::post('/guide', [MobileGuideController::class,'postGuide']);
    Route::post('/guide/{id}', [MobileGuideController::class, 'patchGuide']);
    Route::delete('/guide/{id}', [MobileGuideController::class, 'deleteGuide']);
    
    // Fetch dashboard content
    Route::get('/dashboard', [WebContentController::class,'getDashboard']);
    
    // Fetch faqs content
    Route::get('/faqs', [WebContentController::class,'getFaqs']);
    
    // Fetch pages content
    Route::get('/{page_slug}', [WebContentController::class,'getPage']);
    
    // Search Engine With Calculator
    Route::get('/search/{per_page_count?}', [BookingController::class, 'getMobileSearch']);
    
    // Fetch all dubai cities list
    Route::get('/locations/{lang?}', [ProductsController::class,'carsLocations']);
    
    // Get detail of the car by slug 
    Route::get('/detail/{lang}/{slug}', [ProductsController::class, 'productFetchDetail'])
    ->where('slug', '.*'); 
    
    // Send Enquiry form
    Route::post('/sendEnquiry/{lang?}', [EnquiryController::class, 'offerForm']);

    Route::get('/paymentCallback/{lang?}/{bookingId?}', [PaymentController::class, 'paymentCallback']);
});
