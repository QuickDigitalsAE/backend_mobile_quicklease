<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            direction: ltr;
            text-align: left;
            margin: 0;
            padding: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
        }
        th {
            background-color: #f4f4f4;
        }
        .email-section {
            margin-bottom: 40px;
        }
    </style>
</head>
<body>
    @if($isAdmin)
        <!-- Admin Email Content -->
        <div class="email-section">
            <h1>Customer Review Detail</h1>
            <table>
                <tbody>
                    <tr>
                        <td>Client Name</td>
                        <td>{{ $testimonialDetail['client_name'] }}</td>
                    </tr>
                    <tr>
                        <td>Client Email</td>
                        <td>{{ $testimonialDetail['client_email'] }}</td>
                    </tr>
                    <tr>
                        <td>Client Phone</td>
                        <td>{{ $testimonialDetail['client_phone'] ?? '' }}</td>
                    </tr>
                    @if(isset($testimonialDetail['car_name']) && !empty($testimonialDetail['car_name'])) 
                    <tr>
                        <td>Car Name</td>
                        <td>{{ $testimonialDetail['car_name'] ?? '' }}</td>
                    </tr>
                    @endif
                    @if(isset($testimonialDetail['stars']) && !empty($testimonialDetail['stars'])) 
                    <tr>
                        <td>stars</td>
                        <td>{{ $testimonialDetail['stars'] ?? '' }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td>Client Review</td>
                        <td>{!! $testimonialDetail['client_review'] ?? '' !!}</td>
                    </tr>
                </tbody>
            </table>
            <br>
            <p>Regards,<br> {{ config('app.name') }}</p>
        </div>
    @else
        <!-- User Email Content in Table Form -->
        <div class="email-section">
            <p>Dear {{ $testimonialDetail['client_name'] }},</p>
            <p>Thank you for submitting a request. Our team will contact you soon..</p>
            <table>
                <tbody>
                   <tr>
                        <td>Client Name</td>
                        <td>{{ $testimonialDetail['client_name'] }}</td>
                    </tr>
                    <tr>
                        <td>Client Email</td>
                        <td>{{ $testimonialDetail['client_email'] }}</td>
                    </tr>
                    <tr>
                        <td>Client Phone</td>
                        <td>{{ $testimonialDetail['client_phone'] ?? '' }}</td>
                    </tr>
                    @if(isset($testimonialDetail['car_name']) && !empty($testimonialDetail['car_name'])) 
                    <tr>
                        <td>Car Name</td>
                        <td>{{ $testimonialDetail['car_name'] ?? '' }}</td>
                    </tr>
                    @endif
                    @if(isset($testimonialDetail['stars']) && !empty($testimonialDetail['stars'])) 
                    <tr>
                        <td>stars</td>
                        <td>{{ $testimonialDetail['stars'] ?? '' }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td>Client Review</td>
                        <td>{!! $testimonialDetail['client_review'] ?? '' !!}</td>
                    </tr>
                </tbody>
            </table>
            <br>
            <p>Regards,<br>{{ config('app.name') }}</p>
        </div>
    @endif
</body>
</html>
