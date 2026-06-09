@if($isAdmin)
<div style="width:100%; font-family:Arial, sans-serif; background-color:#f4f4f4; padding:20px; box-sizing:border-box;">
    <div style="max-width:700px; background-color:#ffffff; margin:20px auto; padding:20px; border-radius:8px; box-shadow:0 4px 10px rgba(0,0,0,0.1); line-height:1.6;">

        <img src="{{ asset('logo.png') }}" alt="logo_brand">

        <h5 style="text-align: center;">New "Subscriber Order" Enquire</h5>

        <p style="margin:0 0 15px; font-size:16px; color:#333;">Dear Admin,</p>

        <p style="margin: 0 0 15px; font-size: 16px; color: #333;">
            You have a new subscriber! We are excited to inform you that someone has just joined our community.
        </p>

        <table>
            <tbody>
                <tr>
                    <td>Type</td>
                    <td>Subscription Order</td>
                </tr>
                <tr>
                    <td>Created At</td>
                    <td>{{ $created }}</td>
                </tr>
                <tr>
                    <td>Client Name</td>
                    <td>{{ $user }}</td>
                </tr>
                @if(!empty($email))
                <tr>
                    <td>Client Email</td>
                    <td>{{ $email }}</td>
                </tr>
                @endif
                <tr>
                    <td>Status</td>
                    <td>{{ $status ?? '' }}</td>
                </tr>
            </tbody>
        </table>

        <p>Best regards,<br>{{ config('app.name') }}</p>
    </div>
</div>

@else
<div style="width:100%; font-family:Arial, sans-serif; background-color:#f4f4f4; padding:20px; box-sizing:border-box;">
    <div style="max-width:700px; background-color:#ffffff; margin:20px auto; padding:20px; border-radius:8px; box-shadow:0 4px 10px rgba(0,0,0,0.1); line-height:1.6;">

        <img src="{{ asset('logo.png') }}" alt="logo_brand">

        <h5 style="text-align: center;">New "Subscriber Order" Enquire</h5>

        <p style="margin: 0 0 15px; font-size: 16px; color: #333;">
            Subscription Successful! Thank you for joining our community.
        </p>

        <table>
            <tbody>
                <tr>
                    <td>Type</td>
                    <td>Subscription Order</td>
                </tr>
                <tr>
                    <td>Created At</td>
                    <td>{{ $created }}</td>
                </tr>
                <tr>
                    <td>Client Name</td>
                    <td>{{ $user }}</td>
                </tr>
                @if(!empty($email))
                <tr>
                    <td>Client Email</td>
                    <td>{{ $email }}</td>
                </tr>
                @endif
                <tr>
                    <td>Status</td>
                    <td>{{ $status ?? '' }}</td>
                </tr>
            </tbody>
        </table>

        <p>Regards,<br>{{ config('app.name') }}</p>
    </div>
</div>
@endif