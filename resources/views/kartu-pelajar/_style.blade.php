<style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: Arial, Helvetica, sans-serif;
      background: #64748b;
      color: #0f172a;
    }

    @page {
      size: A4;
      margin: 10mm;
    }

    .toolbar {
      max-width: 210mm;
      margin: 12px auto;
      background: #1e293b;
      color: #e2e8f0;
      border-radius: 12px;
      padding: 10px 14px;
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 12px;
    }

    .toolbar a,
    .toolbar button {
      font-size: 12px;
      font-weight: 700;
      border-radius: 8px;
      padding: 8px 14px;
      border: 1px solid #475569;
      cursor: pointer;
      text-decoration: none;
    }

    .toolbar .back {
      background: #334155;
      color: #e2e8f0;
    }

    .toolbar .print {
      background: #4f46e5;
      color: #fff;
      border-color: #4f46e5;
    }

    .page {
      width: 210mm;
      min-height: 296mm;
      margin: 0 auto 12px;
      background: #fff;
      padding: 10mm;
      display: flex;
      gap: 8mm;
      align-items: flex-start;
    }

    .side-label {
      font-size: 9px;
      font-weight: 700;
      letter-spacing: .08em;
      color: #64748b;
      margin-bottom: 2mm;
      text-transform: uppercase;
    }

    .card {
      width: 85.6mm;
      height: 53.98mm;
      border-radius: 3.55mm;
      overflow: hidden;
      border: .4mm solid #1e3a8a;
      position: relative;
      background: #fff;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
      flex-shrink: 0;
    }

    .card>*:not(.wm):not(.qr-box) {
      position: relative;
      z-index: 1;
    }

    .wm {
      position: absolute;
      inset: -18mm;
      display: flex;
      flex-wrap: wrap;
      align-content: center;
      justify-content: center;
      gap: 2.8mm 5mm;
      transform: rotate(-30deg);
      z-index: 2;
      pointer-events: none;
      overflow: hidden;
    }

    .wm span {
      font-size: 5.8pt;
      font-weight: 800;
      letter-spacing: .08em;
      text-transform: uppercase;
      white-space: nowrap;
      color: rgba(30, 58, 138, .10);
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }

    .qr-box {
      position: absolute;
      right: 2.2mm;
      bottom: 12mm;
      width: 13.5mm;
      z-index: 3;
      background: #fff;
      border: .3mm solid #cbd5e1;
      border-radius: 1.2mm;
      padding: 1mm 1mm .7mm;
      text-align: center;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }

    .qr-box .qr {
      width: 100%;
      height: 11mm;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
    }

    .qr-box .qr img,
    .qr-box .qr canvas {
      width: 100% !important;
      height: 100% !important;
      object-fit: contain;
    }

    .qr-box .cap {
      font-size: 4.3pt;
      font-weight: 800;
      color: #334155;
      margin-top: .6mm;
      letter-spacing: .02em;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .kop {
      background: linear-gradient(135deg, #1e3a8a, #4f46e5);
      color: #fff;
      display: flex;
      align-items: center;
      gap: 2.2mm;
      padding: 1.8mm 2.5mm;
    }

    .kop img {
      width: 8mm;
      height: 8mm;
      object-fit: contain;
      background: transparent;
      border-radius: 1.2mm;
      flex-shrink: 0;
    }

    .kop .kt {
      flex: 1;
      min-width: 0;
      text-align: center;
    }

    .kop .k1 {
      font-size: 7pt;
      font-weight: 800;
      letter-spacing: .12em;
      line-height: 1.15;
    }

    .kop .k2 {
      font-size: 8pt;
      font-weight: 700;
      line-height: 1.2;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .kop .k3 {
      font-size: 5.4pt;
      opacity: .92;
      line-height: 1.25;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .card .body {
      display: flex;
      gap: 2.5mm;
      padding: 2mm 2.5mm;
    }

    .foto {
      width: 20mm;
      height: 26.5mm;
      border-radius: 1.5mm;
      border: .3mm solid #94a3b8;
      object-fit: cover;
      background: #e2e8f0;
      flex-shrink: 0;
    }

    .foto-ph {
      width: 20mm;
      height: 26.5mm;
      border-radius: 1.5mm;
      border: .3mm dashed #94a3b8;
      background: #f1f5f9;
      color: #64748b;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 11pt;
      font-weight: 800;
      flex-shrink: 0;
    }

    .rows {
      flex: 1;
      min-width: 0;
      font-size: 6.6pt;
      line-height: 1.35;
      padding-right: 13mm;
    }

    .rows .nama {
      font-size: 8pt;
      font-weight: 800;
      color: #0f172a;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .biodata {
      width: 100%;
      border-collapse: collapse;
      margin-top: .6mm;
      font-size: 6.6pt;
      line-height: 1.35;
    }

    .biodata td {
      vertical-align: top;
      padding: 0 0 .5mm;
    }

    .biodata .k {
      white-space: nowrap;
      color: #64748b;
      font-size: 5.6pt;
      text-transform: uppercase;
      letter-spacing: .04em;
      padding-top: .4mm;
    }

    .biodata .s {
      width: 2mm;
      text-align: center;
      color: #64748b;
      padding-left: 1mm;
      padding-right: 1mm;
    }

    .biodata .v {
      font-weight: 700;
      color: #0f172a;
      overflow-wrap: break-word;
    }

    .back-title {
      text-align: center;
      font-size: 7pt;
      font-weight: 800;
      letter-spacing: .08em;
      color: #1e3a8a;
      border-bottom: .35mm solid #1e3a8a;
      padding: 1.6mm 2mm 1.2mm;
    }

    .tatib {
      padding: 1.4mm 2.5mm 0 5.5mm;
      font-size: 5.7pt;
      line-height: 1.4;
      color: #1e293b;
    }

    .tatib li {
      margin-bottom: .5mm;
    }

    .ttd {
      display: flex;
      justify-content: flex-end;
      padding: .8mm 2.5mm 0;
    }

    .ttd div {
      text-align: center;
      font-size: 5.7pt;
      color: #334155;
      line-height: 1.35;
    }

    .ttd .cap {
      text-align: center;
    }

    .ttd .sign-zone {
      position: relative;
      display: inline-block;
      margin-top: .4mm;
      min-width: 28mm;
    }

    .ttd img.ttd-img {
      height: 7mm;
      max-width: 20mm;
      object-fit: contain;
      display: block;
      margin: 0 auto;
      position: relative;
      z-index: 1;
    }

    .ttd .ttd-space {
      height: 7mm;
    }

    .ttd img.stempel {
      position: absolute;
      left: 50%;
      top: -2.5mm;
      width: 12mm;
      height: 12mm;
      object-fit: contain;
      transform: translateX(-65%) rotate(-8deg);
      opacity: .85;
      z-index: 2;
      pointer-events: none;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }

    .ttd .sign-name {
      position: relative;
      z-index: 1;
    }

    .foot {
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      background: #1e3a8a;
      color: #fff;
      font-size: 5.4pt;
      text-align: center;
      padding: .9mm;
      letter-spacing: .05em;
    }

    @media print {
      body {
        background: #fff;
      }

      .toolbar {
        display: none !important;
      }

      .page {
        margin: 0;
        padding: 0;
        width: auto;
        min-height: auto;
        page-break-after: always;
      }

      .page:last-child {
        page-break-after: auto;
      }
    }
  </style>
