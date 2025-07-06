<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Cremation Certificate</title>
    <style>
        body {
            font-family: 'Georgia', 'Times New Roman', serif;
            background: #f4f7f8;
            margin: 0;
            color: #213431;
        }
        .certificate-wrapper {
            min-height: 0;
            min-width: 0;
            padding: 0;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .certificate-border {
            background: #fff;
            border: 8px double #156964;
            border-radius: 28px;
            padding: 32px 56px 24px 56px;
            margin: 0 auto;
            max-width: 1080px;
            min-width: 860px;
            min-height: 380px;
            box-shadow: none;
            position: relative;
        }
        .corner-flourish {
            position: absolute;
            width: 62px; height: 62px;
            z-index: 5;
            pointer-events: none;
        }
        .corner-flourish.top-left {
            left: -9px; top: -9px;
            border-top: 6px solid #c9ebe4;
            border-left: 6px solid #c9ebe4;
            border-radius: 35px 0 0 0;
        }
        .corner-flourish.top-right {
            right: -9px; top: -9px;
            border-top: 6px solid #c9ebe4;
            border-right: 6px solid #c9ebe4;
            border-radius: 0 35px 0 0;
        }
        .corner-flourish.bottom-left {
            left: -9px; bottom: -9px;
            border-bottom: 6px solid #c9ebe4;
            border-left: 6px solid #c9ebe4;
            border-radius: 0 0 0 35px;
        }
        .corner-flourish.bottom-right {
            right: -9px; bottom: -9px;
            border-bottom: 6px solid #c9ebe4;
            border-right: 6px solid #c9ebe4;
            border-radius: 0 0 35px 0;
        }
        .certificate-header {
            text-align: center;
            margin-bottom: 7px;
            position: relative;
            z-index: 2;
        }
        .certificate-title {
            color: #156964;
            font-size: 2.9rem;
            font-weight: 900;
            letter-spacing: 3.8px;
            margin-bottom: 0;
            text-shadow: 0 2px 14px #c1dad942;
            font-family: 'Georgia', serif;
            text-transform: uppercase;
        }
        .parlor-name {
            font-size: 1.12rem;
            color: #3e6260;
            margin-bottom: 8px;
            font-weight: 700;
            letter-spacing: 1.3px;
        }
        .certificate-watermark {
            position: absolute;
            left: 0; top: 46%;
            width: 100%;
            text-align: center;
            font-size: 3.9rem;
            font-weight: 800;
            color: #1598861a;
            letter-spacing: 22px;
            z-index: 0;
            user-select: none;
            pointer-events: none;
        }
        .certificate-statement {
            margin: 35px 0 32px 0;
            font-size: 1.24rem;
            line-height: 1.6;
            text-align: center;
            font-family: 'Georgia', serif;
            font-weight: 500;
            color: #183a3a;
        }
        .certificate-details {
            width: 62%;
            margin: 0 auto 32px auto;
            font-size: 1.05rem;
            background: linear-gradient(90deg, #f7fefc 0%, #eaf5f3 100%);
            border-radius: 16px;
            padding: 20px 32px 8px 32px;
            box-shadow: 0 2px 15px #aee4de2c;
            border: 2px solid #b4ebe4;
        }
        .certificate-details-table {
            width: 100%;
            border-collapse: collapse;
        }
        .certificate-details-table td {
            padding: 5px 0;
            font-size: 1.06rem;
        }
        .label {
            color: #13776c;
            font-weight: 700;
            min-width: 170px;
            font-family: 'Georgia', serif;
            font-size: 1.07rem;
            text-align: right;
            padding-right: 22px;
        }
        .certificate-signature-block {
            margin-top: 38px;
            text-align: right;
            position: relative;
        }
        .certificate-signature-block .signature-img {
            display: block;
            margin-right: 0;
            margin-left: auto;
            margin-bottom: 7px;
            max-width: 192px;
            max-height: 54px;
            border-bottom: 2px solid #216663;
            background: #fff;
            border-radius: 3px;
        }
        .certificate-signature-block .parlor-under-signature {
            font-size: 1.13rem;
            font-weight: bold;
            color: #156964;
            margin-top: 10px;
            text-align: right;
        }
        .certificate-footer {
            margin-top: 30px;
            font-size: 1.03rem;
            color: #888;
            text-align: center;
            font-style: italic;
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body>
<div class="certificate-wrapper">
    <div class="certificate-border">

        <div class="certificate-watermark">CERTIFIED</div>
        <div class="certificate-header">
            <div class="certificate-title">Cremation Certificate</div>
            <div class="parlor-name">{{ $funeralParlorName }}</div>
        </div>

        <div class="certificate-statement">
            This is to certify that <b>{{ $deceasedName }}</b>,
            who passed away on <b>{{ $dateOfDeath }}</b>,
            has been duly cremated at <b>{{ $funeralParlorName }}</b> on <b>{{ $cremationDate }}</b>.
        </div>

<div class="certificate-details">
    <table class="certificate-details-table">
        <tr>
            <td class="label">Deceased Name:</td>
            <td>{{ $deceasedName }}</td>
        </tr>
        <tr>
            <td class="label">Date of Death:</td>
            <td>{{ $dateOfDeath }}</td>
        </tr>
        <tr>
            <td class="label">Cremation Date:</td>
            <td>{{ $cremationDate }}</td>
        </tr>
        <tr>
            <td class="label">Certificate Issued:</td>
            <td>{{ $issuedDate }}</td>
        </tr>
    </table>
</div>

        <div class="certificate-signature-block">
            @if($signatureImage)
                <img src="{{ $signatureImage }}" class="signature-img">
            @endif
            <div class="parlor-under-signature">{{ $funeralParlorName }}</div>
        </div>

        <div class="certificate-footer">
            <em>
                This certificate is issued electronically and valid without a physical seal.<br>
                Verified by EternaLink&trade; | {{ now()->format('F d, Y') }}
            </em>
        </div>
    </div>
</div>
</body>
</html>
