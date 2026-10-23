# Laravel Algeria Invoice — الفاتورة الجزائرية

[![Tests](https://github.com/djouamaah/laravel-algeria-invoice/actions/workflows/tests.yml/badge.svg)](https://github.com/djouamaah/laravel-algeria-invoice/actions/workflows/tests.yml)

**[English documentation ↓](#english)**

حزمة لارافل لإعداد فواتير جزائرية:

- **الحساب:** الرسم على القيمة المضافة لكل نسبة (19% و9% والمعفى)، وحقوق الطابع على الدفع نقدًا حسب قانون المالية 2025.
- **البيانات الإلزامية:** فحص الفاتورة وفق المرسوم التنفيذي 05-468، مع رسائل بالعربية والفرنسية والإنجليزية.
- **الترقيم:** متسلسل بلا فجوات، يُعاد كل سنة (`FA-2026-00001`)، ولا يتأثر بطلبين في نفس اللحظة.
- **المبلغ بالحروف:** بالعربية والفرنسية («ألف ومئتان وخمسون دينارًا جزائريًا وخمسون سنتيمًا»).
- **الطباعة:** ثلاثة قوالب جاهزة (`classic` و`modern` و`minimal`) أو قالبك الخاص، بالعربية أو الفرنسية، وملف PDF عبر mPDF عند تثبيته.
- **أنواع الوثائق:** فاتورة، وإشعار دائن (Avoir)، وفاتورة شكلية (Proforma).
- **المراجع:** إسناد الفاتورة إلى سند الطلب وإلى وصل تسليم أو أكثر، مع أرقامها وتواريخها.

الحزمة لا تحفظ الفواتير في قاعدة البيانات، فتطبيقك يحفظها في نماذجه الخاصة (`toArray()` و`fromArray()`). الجدول الوحيد الذي تنشئه هو جدول الترقيم.

يدعم Laravel 10 و11 و12 و13، وPHP 8.1 فما فوق.

> ⚠️ **تنبيه قانوني:** هذه الحزمة أداة مساعدة ولا تعوّض المحاسب. بعض قواعد حقوق الطابع غير محسومة في المصادر المتاحة (انظر [حقوق الطابع](#حقوق-الطابع))، وكل القواعد قابلة للتعديل من ملف الإعدادات. تحقّق منها مع محاسبك أو مركز الضرائب قبل الاستعمال الفعلي.

---

## التثبيت

```bash
composer require djouamaah/laravel-algeria-invoice
php artisan migrate
```

لإخراج PDF (يدعم تشكيل الحروف العربية):

```bash
composer require mpdf/mpdf
```

انشر ملف الإعدادات وضع فيه معلومات مؤسستك:

```bash
php artisan vendor:publish --tag=algeria-invoice-config
```

```php
// config/algeria-invoice.php
'seller' => [
    'name' => 'SARL Exemple',
    'legal_form' => 'SARL',
    'capital' => 1000000,
    'address' => '12 rue Didouche Mourad, Alger',
    'rc' => '16/00-1234567B21',
    'nif' => '000116123456789',
    'nis' => '000116010203040',
    'ai' => '16012345678',
],
```

---

## الاستعمال

### إنشاء فاتورة

```php
use Djouamaah\AlgeriaInvoice\Facades\AlgeriaInvoice;
use Illuminate\Support\Facades\DB;

$invoice = AlgeriaInvoice::make()            // البائع من ملف الإعدادات
    ->buyer([
        'name' => 'EURL Client',
        'legal_form' => 'EURL',
        'address' => 'Zone industrielle, Sétif',
        'rc' => '19/00-7654321B20',
        'nif' => '000219987654321',
    ])
    ->date('2026-10-23')
    ->addLine('Ordinateur portable', 2, 85000, 19)              // الوصف، الكمية، سعر الوحدة دون رسوم (دج)، النسبة
    ->addLine('Livres scolaires', 30, 1200, 9, 'pièce', 5)     // الوحدة، تخفيض 5%
    ->addLine('Formation', 1, 15000, 0)                         // معفى
    ->payment('cash');                                          // cash, cheque, transfer, electronic, other
```

### المجاميع

كل المبالغ أعداد صحيحة **بالسنتيم** (1 دج = 100 سنتيم)، لتفادي أخطاء التقريب.

```php
$totals = $invoice->totals();

$totals->totalHt;        // 21920000  (219 200,00 دج)
$totals->vatBreakdown;   // ['19' => ['base' => 17000000, 'vat' => 3230000], '9' => [...], '0' => [...]]
$totals->totalVat;       //  3537800
$totals->totalTtc;       // 25457800
$totals->stampDuty;      //   509200  (نقدًا فقط)
$totals->netToPay;       // 25967000

\Djouamaah\AlgeriaInvoice\Money::format($totals->netToPay);        // "259 670,00 DA"
\Djouamaah\AlgeriaInvoice\Money::format($totals->netToPay, 'ar');  // "259.670,00 دج"
```

### التحقق من البيانات الإلزامية

```php
$invoice->isCompliant();   // false
$invoice->errors();        // ['number' => 'رقم الفاتورة إلزامي.', 'seller.nis' => ...]
```

ما يُفحص:
- **البائع:** الاسم، العنوان، الشكل القانوني، السجل التجاري، NIF، NIS، رقم المادة الجبائية. ورأس المال الاجتماعي للشركات (SARL وEURL وSPA…).
- **الزبون:** الاسم والعنوان. وإن كان مهنيًا: السجل التجاري وNIF.
- **الفاتورة:** الرقم، الأسطر (الوصف والكمية والسعر والنسبة)، طريقة الدفع. والإشعار الدائن يذكر رقم الفاتورة الأصلية.
- **الصيغ:** NIF من 15 أو 20 رقمًا، NIS من 15 رقمًا، RIB من 20 رقمًا.

### الترقيم بلا فجوات

اطلب الرقم **داخل نفس المعاملة** التي تحفظ الفاتورة. إذا فشل الحفظ، يُلغى الرقم ولا تبقى فجوة في التسلسل:

```php
DB::transaction(function () use ($invoice) {
    $invoice->number(AlgeriaInvoice::nextNumber());           // FA-2026-00001
    MyInvoice::create(['number' => $invoice->getNumber(), 'data' => $invoice->toArray()]);
});

AlgeriaInvoice::nextNumber('credit_note');   // AV-2026-00001
AlgeriaInvoice::nextNumber('proforma');      // PF-2026-00001
AlgeriaInvoice::sequence()->current();       // آخر رقم مستعمل
```

صيغة الرقم قابلة للتعديل: `'format' => '{series}-{year}-{number}'`، و`'pad' => 5`.

### سند الطلب ووصولات التسليم

```php
$invoice
    ->order('BC-2026-118', '2026-10-20')              // سند الطلب: الرقم والتاريخ
    ->deliveryNote('BL-2026-0451', '2026-10-21')      // وصل تسليم
    ->deliveryNote('BL-2026-0458', '2026-10-22');     // فاتورة تجميعية لعدة وصولات

$invoice->getOrder()->number;      // "BC-2026-118"
$invoice->getDeliveryNotes();      // [Reference, Reference]
$invoice->deliveryNotes(['BL-1', ['number' => 'BL-2', 'date' => '2026-10-21']]);   // استبدال القائمة
```

- **في الفاتورة:** تظهر المراجع بجانب رقمها، كل وصل في سطر مستقل («وصل التسليم : BL-2026-0451 بتاريخ 21/10/2026»).
- **الحفظ:** تُحفظ في `toArray()` تحت المفاتيح `order_reference` و`order_date` و`delivery_notes`.
- **التوافق مع 1.0:** `orderReference('BC-118')` ما زالت تعمل.

لترقيم وصولات التسليم بسلسلة خاصة بها:

```php
AlgeriaInvoice::nextNumber(series: 'BL');   // BL-2026-00001
```

### الإشعار الدائن والفاتورة الشكلية

```php
AlgeriaInvoice::make('credit_note')->original('FA-2026-00001')->...;
AlgeriaInvoice::make('proforma')->...;    // بلا رقم إلزامي ولا حقوق طابع
```

### المبلغ بالحروف

```php
AlgeriaInvoice::words(125050);         // "mille deux cent cinquante dinars algériens et cinquante centimes"
AlgeriaInvoice::words(125050, 'ar');   // "ألف ومئتان وخمسون دينارًا جزائريًا وخمسون سنتيمًا"
```

### الطباعة و PDF

```php
return AlgeriaInvoice::html($invoice, 'ar');       // صفحة HTML جاهزة للطباعة (ar أو fr أو en)
$pdf = AlgeriaInvoice::pdf($invoice, 'fr');        // محتوى ملف PDF
return AlgeriaInvoice::download($invoice, 'ar');   // تنزيل FA-2026-00001.pdf
```

### القوالب

| القالب | الشكل |
|---|---|
| `classic` (الافتراضي) | البائع في الأعلى، إطار للزبون، ورأس جدول أخضر |
| `modern` | شريط ملوّن في الأعلى، البائع والزبون جنبًا إلى جنب، والصافي للدفع بارز |
| `minimal` | أبيض وأسود بجدول مُسطَّر، مناسب لأي طابعة، مع خانة «الختم والإمضاء» |

```php
AlgeriaInvoice::pdf($invoice, 'ar', template: 'modern');
AlgeriaInvoice::html($invoice, 'fr', template: 'minimal');
AlgeriaInvoice::download($invoice, 'ar', template: 'minimal');
AlgeriaInvoice::templates();   // ['classic' => ..., 'modern' => ..., 'minimal' => ..., + قوالبك]
```

القالب الافتراضي في `config/algeria-invoice.php`: `'template' => 'classic'`.

**قالبك الخاص:** أنشئ ملف Blade، ثم سجّله باسم في ملف الإعدادات:

```php
// config/algeria-invoice.php
'templates' => [
    'shop' => 'invoices.shop',   // resources/views/invoices/shop.blade.php
],
```

في القالب يوجد المتغيّر `$v`، وفيه كل شيء جاهز ومترجَم:
- `$v->title()` و`$v->references()` و`$v->sentence()`.
- `$v->money($centimes)` و`$v->vatRows()` و`$v->partyLines($party)` و`$v->partyIds($party)`.
- `$v->invoice` و`$v->totals`، و`$v->t('labels.net_to_pay')` للترجمة.

ويمكنك أيضًا استعمال أجزاء القوالب الجاهزة:
- `algeria-invoice::partials.lines`
- `algeria-invoice::partials.totals`
- `algeria-invoice::partials.party`

لتعديل القوالب الجاهزة نفسها: `php artisan vendor:publish --tag=algeria-invoice-views`.

### الحفظ في تطبيقك

```php
$data = $invoice->toArray();          // مصفوفة قابلة للحفظ في عمود JSON
$invoice = Invoice::fromArray($data); // المجاميع تُعاد حسابها
```

---

## حقوق الطابع

حقوق الطابع على الدفع نقدًا (المادة 100-I من قانون الطابع، المعدَّلة بالمادة 46 من قانون المالية 2025):

| المبلغ | الحق |
|---|---|
| أكثر من 300 دج إلى 30 000 دج | 1 دج لكل 100 دج |
| أكثر من 30 000 دج إلى 100 000 دج | 1,5 دج لكل 100 دج |
| أكثر من 100 000 دج | 2 دج لكل 100 دج |

- **بلا سقف.** كل 100 دج بدأت تُحسب كاملة.
- **الدفع الإلكتروني معفى** (المادة 258 خامسًا، قانون المالية 2025). الصك والتحويل لا يخضعان لهذا الحق.

**نقاط غير محسومة في المصادر.** كلها قابلة للتعديل في `config/algeria-invoice.php`:

| الإعداد | الافتراضي | السؤال |
|---|---|---|
| `mode` | `whole` | هل تُطبَّق النسبة على كامل المبلغ (`whole`) أم على كل شريحة وحدها (`marginal`)؟ |
| `exempt_up_to` | `300` | هل المبالغ حتى 300 دج معفاة؟ |
| `minimum` | `5` | الحد الأدنى 5 دج (المادة 100) أم 30 دج (المادة 2 بعد قانون المالية 2025)؟ |
| `base` | `ttc` | الأساس هو المبلغ بكل الرسوم أم دون رسوم؟ |

---

## الاختبارات

```bash
composer install
composer test
```

## الترخيص

MIT

---

## English

Algerian invoices for Laravel. All amounts are integers in **centimes**.

- VAT per rate (19%, 9%, exempt) and the **cash stamp duty** of the Finance Law 2025 (art. 100-I of the Code du timbre: 1 / 1.5 / 2 DA per 100 DA, no cap, electronic payments exempt).
- **Mandatory mentions** check (executive decree 05-468) with messages in Arabic, French and English.
- **Gap-free numbering** per series and year (`FA-2026-00001`), safe under concurrency.
- **Amounts in words** in Arabic and French.
- Printable **HTML** in Arabic (RTL), French or English, and **PDF** through `mpdf/mpdf` when installed, with three **templates** (`classic`, `modern`, `minimal`) or your own.
- Invoices, credit notes (avoirs) and proforma invoices. Nothing is stored: keep `toArray()` in your own models.
- Link an invoice to its **purchase order** and one or more **delivery notes**, with numbers and dates.

> ⚠️ This is a helper, not tax advice. Some stamp duty details are unclear in the available sources; every rule is configurable. Check them with your accountant.

```bash
composer require djouamaah/laravel-algeria-invoice
php artisan migrate
composer require mpdf/mpdf   # optional, for PDF
```

```php
use Djouamaah\AlgeriaInvoice\Facades\AlgeriaInvoice;

$invoice = AlgeriaInvoice::make()                 // seller from config('algeria-invoice.seller')
    ->buyer(['name' => 'EURL Client', 'address' => 'Sétif', 'rc' => '19/00-7654321B20', 'nif' => '000219987654321'])
    ->addLine('Laptop', 2, 85000, 19)             // description, quantity, unit price excl. VAT (DA), VAT rate
    ->addLine('Books', 30, 1200, 9, 'pcs', 5)     // unit, 5% discount
    ->payment('cash')
    ->order('BC-2026-118', '2026-10-20')          // purchase order (bon de commande)
    ->deliveryNote('BL-2026-0451', '2026-10-21')  // delivery notes (bons de livraison), as many as needed
    ->deliveryNote('BL-2026-0458', '2026-10-22');

$invoice->totals()->netToPay;                     // centimes, stamp duty included
$invoice->errors();                               // missing mandatory mentions, [] when compliant

DB::transaction(function () use ($invoice) {      // the number is only consumed if saving succeeds
    $invoice->number(AlgeriaInvoice::nextNumber());
    MyInvoice::create(['number' => $invoice->getNumber(), 'data' => $invoice->toArray()]);
});

AlgeriaInvoice::words(125050, 'ar');              // ألف ومئتان وخمسون دينارًا جزائريًا وخمسون سنتيمًا
return AlgeriaInvoice::download($invoice, 'ar');  // PDF
```

Templates: `classic` (default), `modern` (coloured header, side-by-side parties) and `minimal` (black and white grid with a signature box).

```php
AlgeriaInvoice::pdf($invoice, 'ar', template: 'modern');

// config/algeria-invoice.php: your own Blade view, which receives $v (an InvoiceView with everything translated and formatted)
'template' => 'classic',
'templates' => ['shop' => 'invoices.shop'],
```

Stamp duty settings (`mode`, `exempt_up_to`, `minimum`, `maximum`, `base`, `brackets`) live in `config/algeria-invoice.php`; see the table above for the open questions.

## License

MIT
