<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            direction: rtl;
            text-align: right;
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
            <h1>تفاصيل مراجعة العميل</h1>
            <table>
                <tbody>
                    <tr>
                        <td>اسم العميل</td>
                        <td>{{ $testimonialDetail['client_name'] }}</td>
                    </tr>
                    <tr>
                        <td>البريد الإلكتروني للعميل</td>
                        <td>{{ $testimonialDetail['client_email'] }}</td>
                    </tr>
                    <tr>
                        <td>هاتف العميل</td>
                        <td>{{ $testimonialDetail['client_phone'] ?? '' }}</td>
                    </tr>
                    <tr>
                        <td>إسم السيارة</td>
                        <td>{{ $testimonialDetail['car_name'] ?? '' }}</td>
                    </tr>
                    <tr>
                        <td>مراجعة العميل</td>
                        <td>{!! $testimonialDetail['client_review'] ?? '' !!}</td>
                    </tr>
                </tbody>
            </table>
            <br>
            <p>تحياتنا،<br> {{ config('app.name') }}</p>
        </div>
    @else
        <!-- User Email Content in Table Form -->
        <div class="email-section">
            <p>عزيزي/عزيزتي {{ $testimonialDetail['client_name'] }},</p>
            <p>شكرًا لتقديم طلبك. سيتواصل فريقنا معك قريبًا.</p>
            <table>
                <tbody>
                    <tr>
                        <td>اسم العميل</td>
                        <td>{{ $testimonialDetail['client_name'] }}</td>
                    </tr>
                    <tr>
                        <td>البريد الإلكتروني للعميل</td>
                        <td>{{ $testimonialDetail['client_email'] }}</td>
                    </tr>
                    <tr>
                        <td>هاتف العميل</td>
                        <td>{{ $testimonialDetail['client_phone'] ?? '' }}</td>
                    </tr>
                    <tr>
                        <td>إسم السيارة</td>
                        <td>{{ $testimonialDetail['car_name'] ?? '' }}</td>
                    </tr>
                    <tr>
                        <td>مراجعة العميل</td>
                        <td>{!! $testimonialDetail['client_review'] ?? '' !!}</td>
                    </tr>
                </tbody>
            </table>
            <br>
            <p>يرجى الاحتفاظ بهذه المعلومات لسجلاتك.</p>
            <p>تحياتنا،<br>{{ config('app.name') }}</p>
        </div>
    @endif
</body>
</html>
