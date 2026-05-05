@extends('layouts.admin')

@section('title', 'Settings')

@section('content')
<form method="POST" action="{{ route('admin.settings') }}">
    @csrf

    <!-- Company Details -->
    <div class="card mb-2">
        <div class="card-header">Company Details</div>
        <div class="card-body">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div class="form-group">
                    <label>Company Name</label>
                    <input type="text" name="company_name" class="form-control"
                           value="{{ $settings['company_name'] ?? 'OmniSpace 3D Events Ltd' }}">
                </div>
                <div class="form-group">
                    <label>Company Website</label>
                    <input type="text" name="company_website" class="form-control"
                           value="{{ $settings['company_website'] ?? 'www.omnispace3d.com' }}">
                </div>
                <div class="form-group">
                    <label>Company Address</label>
                    <input type="text" name="company_address" class="form-control"
                           value="{{ $settings['company_address'] ?? '' }}"
                           placeholder="e.g. Nairobi, Kenya">
                </div>
                <div class="form-group">
                    <label>PIN / VAT Number</label>
                    <input type="text" name="company_pin" class="form-control"
                           value="{{ $settings['company_pin'] ?? '' }}">
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="company_phone" class="form-control"
                           value="{{ $settings['company_phone'] ?? '+254 731 001 723' }}">
                </div>
                <div class="form-group">
                    <label>WhatsApp Number</label>
                    <input type="text" name="company_whatsapp" class="form-control"
                           value="{{ $settings['company_whatsapp'] ?? '+254731001723' }}">
                </div>
                <div class="form-group">
                    <label>Company Email (receives BCC of all orders)</label>
                    <input type="email" name="company_email" class="form-control"
                           value="{{ $settings['company_email'] ?? '' }}">
                </div>
            </div>
        </div>
    </div>

    <!-- SMTP / Email -->
    <div class="card mb-2">
        <div class="card-header">Email / SMTP Settings</div>
        <div class="card-body">
            <div class="alert alert-info" style="font-size:12px;">
                OmniShop uses Gmail SMTP. Use an App Password (not your Gmail password) — enable 2FA first,
                then generate at <a href="https://myaccount.google.com/apppasswords" target="_blank">myaccount.google.com/apppasswords</a>.
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div class="form-group">
                    <label>SMTP Host</label>
                    <input type="text" name="smtp_host" class="form-control"
                           value="{{ $settings['smtp_host'] ?? 'smtp.gmail.com' }}">
                </div>
                <div class="form-group">
                    <label>SMTP Port</label>
                    <input type="number" name="smtp_port" class="form-control"
                           value="{{ $settings['smtp_port'] ?? '587' }}">
                </div>
                <div class="form-group">
                    <label>Gmail Address (SMTP Username)</label>
                    <input type="email" name="smtp_user" class="form-control"
                           value="{{ $settings['smtp_user'] ?? '' }}"
                           placeholder="yourname@gmail.com">
                </div>
                <div class="form-group">
                    <label>Gmail App Password</label>
                    <input type="password" name="smtp_password" class="form-control"
                           placeholder="Leave blank to keep current password">
                    <small style="color:#6E6E6E;">Leave blank to keep existing password.</small>
                </div>
                <div class="form-group">
                    <label>From Email</label>
                    <input type="email" name="smtp_from_email" class="form-control"
                           value="{{ $settings['smtp_from_email'] ?? $settings['smtp_user'] ?? '' }}"
                           placeholder="orders@omnispace3d.com">
                </div>
                <div class="form-group">
                    <label>From Name</label>
                    <input type="text" name="smtp_from_name" class="form-control"
                           value="{{ $settings['smtp_from_name'] ?? 'OmniSpace 3D Events' }}">
                </div>
            </div>
        </div>
    </div>

    <!-- Catalog Passwords per Event -->
    <div class="card mb-2">
        <div class="card-header">Catalog Access Passwords</div>
        <div class="card-body">
            <p style="font-size:13px;color:#6E6E6E;margin-bottom:16px;">
                Set the password clients use to access each event catalog.
            </p>
            @foreach($events as $slug => $ev)
            <div style="border:1px solid #D6F0EF;border-radius:6px;padding:14px;margin-bottom:12px;">
                <strong style="color:#0A9696;">{{ $ev['name'] }}</strong>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:10px;">
                    <div class="form-group">
                        <label>Client Password</label>
                        <input type="text" name="catalog_password_{{ $slug }}" class="form-control"
                               placeholder="Leave blank to keep current"
                               value="{{ $settings['catalog_password_'.$slug] ?? '' }}">
                    </div>
                    <div class="form-group">
                        <label>Demo / Staff Password</label>
                        <input type="text" name="catalog_demo_password_{{ $slug }}" class="form-control"
                               placeholder="Leave blank to keep current"
                               value="{{ $settings['catalog_demo_password_'.$slug] ?? '' }}">
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Invoice Content -->
    <div class="card mb-2">
        <div class="card-header">Invoice Content</div>
        <div class="card-body">
            <div class="form-group">
                <label>Payment Instructions</label>
                <textarea name="payment_instructions" class="form-control" rows="4">{{ $settings['payment_instructions'] ?? "Payment is due within 7 days of invoice date.\nBank transfer details will be provided upon request." }}</textarea>
            </div>
            <div class="form-group">
                <label>Terms & Conditions</label>
                <textarea name="invoice_tc" class="form-control" rows="5">{{ $settings['invoice_tc'] ?? "1. All prices are in USD and subject to applicable taxes." }}</textarea>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary btn-lg">Save All Settings</button>

</form>
@endsection
