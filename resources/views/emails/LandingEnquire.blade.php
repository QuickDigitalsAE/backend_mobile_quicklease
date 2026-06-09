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

        th,
        td {
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
        <h1>Request Form Detail</h1>
        <table>
            <tbody>
                <tr>
                    <td>Client Name</td>
                    <td>{{ $data['name'] }}</td>
                </tr>
                @if(!empty($data['email']))
                <tr>
                    <td>Client Email</td>
                    <td>{{ $data['email'] }}</td>
                </tr>
                @endif
                <tr>
                    <td>Client Contract Number</td>
                    <td>{{ $data['mobile'] ?? '' }}</td>
                </tr>

                <tr>
                    <td>Request Model</td>
                    <td>{{ $data['modal'] ?? '' }}</td>
                </tr>
            </tbody>
        </table>
        <br>
        <p>Regards,<br> {{ config('app.name') }}</p>
    </div>
    @else
    <!-- User Email Content in Table Form -->
    <div class="email-section">
        <p>Dear {{ $data['name'] }},</p>
        <p>Thank you for submitting a request. Our team will contact you soon..</p>
        <table>
            <tbody>
                <tr>
                    <td>Client Name</td>
                    <td>{{ $data['name'] }}</td>
                </tr>
                @if(!empty($data['email']))
                <tr>
                    <td>Client Email</td>
                    <td>{{ $data['email'] }}</td>
                </tr>
                @endif
                <tr>
                    <td>Client Contract Number</td>
                    <td>{{ $data['mobile'] ?? '' }}</td>
                </tr>

                <tr>
                    <td>Request Model</td>
                    <td>{{ $data['modal'] ?? '' }}</td>
                </tr>
            </tbody>
        </table>
        <br>
        <p>Please keep this information for your records.</p>
        <p>Regards,<br>{{ config('app.name') }}</p>
    </div>
    @endif
</body>

</html>