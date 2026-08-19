<?php

namespace App\Services\Taxation;

/**
 * Canonical taxation catalog keyed by the source CRM service_id values.
 *
 * @phpstan-type CatalogRow array{id: int, category: string, name: string, price: float}
 */
class TaxationCatalog
{
    public const DEFAULT_COMMISSION = '2.00';

    public const SERVICE_ACCESS_KEY = 'taxation';

    /**
     * @return list<array{name: string, slug: string}>
     */
    public static function categories(): array
    {
        $names = [];
        foreach (self::services() as $service) {
            $names[$service['category']] = true;
        }

        $sort = 1;
        $categories = [];
        foreach (array_keys($names) as $name) {
            $categories[] = [
                'name' => $name,
                'slug' => strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $name) ?? $name, '-')),
                'sort_order' => $sort++,
            ];
        }

        return $categories;
    }

    /**
     * @return list<CatalogRow>
     */
    public static function services(): array
    {
        $cInc = 'Company And LLP And NGO Incorporation';
        $cGst = 'GST';
        $cItr = 'Income Tax';
        $cDsc = 'DSC';
        $cFssai = 'FSSAI';
        $cIec = 'Import - Export Services (Iec)';
        $cIso = 'Iso Certification And Surveillance';
        $cPf = 'PF & ESI & Payroll Services';
        $cCma = 'Project Report And Cma Report';
        $cTm = 'Trademark And Intellectual Rights';
        $cLicence = 'Licences';
        $cMsme = 'Msme Or Udyog Aadhaar Or Udyami Services';
        $cPartnership = 'Partnership Services';
        $cStartup = 'Startup India Services';

        return [
            ['id' => 1, 'category' => $cInc, 'name' => 'PRIVATE LIMITED COMPANY REGISTRATION', 'price' => 5499],
            ['id' => 2, 'category' => $cInc, 'name' => 'LLP (Limited liability partnership) registration upto 1Lac Contribution (excluding Stamp Duty)', 'price' => 5218],
            ['id' => 3, 'category' => $cInc, 'name' => 'OPC REGISTRATION', 'price' => 4888],
            ['id' => 4, 'category' => $cInc, 'name' => 'SECTION 8 | NGO | Trust REGISTRATION', 'price' => 7799],
            ['id' => 5, 'category' => $cInc, 'name' => 'COMPULSORY CONVERSION OF OPC INTO PRIVATE LIMITED COMPANY', 'price' => 8978],
            ['id' => 6, 'category' => $cInc, 'name' => 'CONVERSION OF PRIVATE INTO PUBLIC LIMITED', 'price' => 14642],
            ['id' => 7, 'category' => $cInc, 'name' => 'CONVERSION OF PRIVATE LIMITED TO OPC', 'price' => 7090],
            ['id' => 8, 'category' => $cInc, 'name' => 'VOLUNTARY CONVERSION OF OPC INTO PRIVATE LIMITED', 'price' => 11254],
            ['id' => 9, 'category' => $cInc, 'name' => 'Conversion of section 8 company into company of any other kind (INC-18)', 'price' => 1869],
            ['id' => 10, 'category' => $cInc, 'name' => 'Conversion of Private Limited Company to public limited company', 'price' => 14642],
            ['id' => 11, 'category' => $cInc, 'name' => 'NIDHI Registration (Only Professional Fees) Excl. Government Fees', 'price' => 13999],
            ['id' => 12, 'category' => $cInc, 'name' => 'Name Approval Only for Company', 'price' => 190],
            ['id' => 13, 'category' => $cInc, 'name' => 'LIP ( LIMITED LIABIABILITY PARTNERSHIP)', 'price' => 5218],
            ['id' => 16, 'category' => $cInc, 'name' => 'NIDHI REGISTRATION', 'price' => 13999],
            ['id' => 17, 'category' => $cInc, 'name' => 'NAME APPROVAL ONLY COMPANY', 'price' => 190],
            ['id' => 18, 'category' => $cItr, 'name' => 'BACK DATE ITR4(Turnover Upto ₹ 50Lacs)', 'price' => 500],
            ['id' => 179, 'category' => $cInc, 'name' => 'PRIVATE LIMITED COMPANY REGISTRATION', 'price' => 5499],
            ['id' => 180, 'category' => $cInc, 'name' => 'LIP ( LIMITED LIABIABILITY PARTNERSHIP)', 'price' => 5218],
            ['id' => 181, 'category' => $cInc, 'name' => 'OPC REGISTRATION', 'price' => 4888],
            ['id' => 182, 'category' => $cInc, 'name' => 'SECTION8| NGO| TRUST REGISTRATION', 'price' => 7799],
            ['id' => 183, 'category' => $cInc, 'name' => 'NIDHI REGISTRATION', 'price' => 13999],
            ['id' => 184, 'category' => $cInc, 'name' => 'NAME APPROVAL ONLY COMPANY', 'price' => 190],
            ['id' => 185, 'category' => $cInc, 'name' => 'NAME APPROVAL ONLY FOR LIP', 'price' => 190],
            ['id' => 186, 'category' => $cInc, 'name' => 'ROC ANNUAL COMPLIANCE STARTUP UPPER PLAN', 'price' => 19196],
            ['id' => 187, 'category' => $cInc, 'name' => 'ANNUAL SECTION-8COMPANY COMPLIANCE', 'price' => 7562],
            ['id' => 188, 'category' => $cDsc, 'name' => 'DSC (DIGITAL SIGNATURE CERTIFICATE) DSC DGFT (2YEAR)', 'price' => 1957],
            ['id' => 189, 'category' => $cDsc, 'name' => 'DSC - CLASS 3 (2YEAR)', 'price' => 809],
            ['id' => 190, 'category' => $cDsc, 'name' => 'DSC CLASS 3 COMBO (2YEAR)', 'price' => 1359],
            ['id' => 191, 'category' => $cFssai, 'name' => 'FSSAI AND FOSCOS BASIC', 'price' => 423],
            ['id' => 192, 'category' => $cFssai, 'name' => 'FSSAI AND FOSCOS STATE', 'price' => 600],
            ['id' => 193, 'category' => $cFssai, 'name' => 'FSSAI AND CENTRAL', 'price' => 1190],
            ['id' => 194, 'category' => $cGst, 'name' => 'GST REGISTRATION (REGULAR)', 'price' => 700],
            ['id' => 195, 'category' => $cGst, 'name' => 'GST REGISTRATION (COMPOSITION SCHEME)', 'price' => 700],
            ['id' => 196, 'category' => $cGst, 'name' => 'GST REGISTRATION (E-COMMERCE)', 'price' => 999],
            ['id' => 197, 'category' => $cGst, 'name' => 'GST FINAL RETURN', 'price' => 908],
            ['id' => 198, 'category' => $cGst, 'name' => 'GSTR-3B ( UPTO 30 INVOICES)', 'price' => 500],
            ['id' => 199, 'category' => $cGst, 'name' => 'GSTR-3B ( UPTO 50 INVOICES)', 'price' => 700],
            ['id' => 200, 'category' => $cGst, 'name' => 'GSTR-3B ( UPTO 100 INVOICES)', 'price' => 1200],
            ['id' => 201, 'category' => $cGst, 'name' => 'GSTR-3B ( UPTO 200 INVOICES)', 'price' => 2000],
            ['id' => 202, 'category' => $cGst, 'name' => 'GST NILL RETURN', 'price' => 246],
            ['id' => 203, 'category' => $cGst, 'name' => 'GST Return For Composition Dealers (Jan-Mar | 4Th Quarter)', 'price' => 423],
            ['id' => 204, 'category' => $cGst, 'name' => 'GST Return For Composition Dealers (July-Sept | 2Nd Quarter)', 'price' => 423],
            ['id' => 205, 'category' => $cGst, 'name' => 'GST Return For Composition Dealers (Apr-June | 1St Quarter)', 'price' => 423],
            ['id' => 206, 'category' => $cGst, 'name' => 'GST Return For Composition Dealers (Oct-Nov | 3Rd Quarter)', 'price' => 423],
            ['id' => 207, 'category' => $cIec, 'name' => 'Iec (Import Export Code) Plan', 'price' => 1190],
            ['id' => 208, 'category' => $cIec, 'name' => 'Iec Code Amendment', 'price' => 1072],
            ['id' => 209, 'category' => $cItr, 'name' => 'ITR-1 (Upto ₹ 10 Lacs)', 'price' => 499],
            ['id' => 210, 'category' => $cItr, 'name' => 'ITR-1 (Upto ₹ 25 Lacs)', 'price' => 999],
            ['id' => 211, 'category' => $cItr, 'name' => 'ITR-1 (Upto ₹ 50Lacs)', 'price' => 2000],
            ['id' => 212, 'category' => $cItr, 'name' => 'ITR-2 (Upto ₹ 25 Lacs)', 'price' => 400],
            ['id' => 213, 'category' => $cItr, 'name' => 'ITR-2 (Upto ₹ 50 Lacs)', 'price' => 820],
            ['id' => 214, 'category' => $cItr, 'name' => 'ITR-2 (Above ₹ 50 Lacs)', 'price' => 1820],
            ['id' => 215, 'category' => $cItr, 'name' => 'ITR-3 (Turnover Upto ₹ 20 Lacs)', 'price' => 799],
            ['id' => 216, 'category' => $cItr, 'name' => 'ITR-3 (Turnover Upto ₹ 50 Lacs)', 'price' => 1299],
            ['id' => 217, 'category' => $cItr, 'name' => 'ITR-3 (Turnover Upto ₹ 75 Lacs)', 'price' => 1500],
            ['id' => 218, 'category' => $cItr, 'name' => 'ITR-3 (Turnover Upto ₹ 1 Crore)', 'price' => 2500],
            ['id' => 220, 'category' => $cItr, 'name' => 'ITR-4 (Turnover Upto ₹ 1 Crore)', 'price' => 999],
            ['id' => 221, 'category' => $cItr, 'name' => 'Form 15Ca (Form Payment Of Import To Non RESIdent Person)', 'price' => 3800],
            ['id' => 222, 'category' => $cItr, 'name' => 'Form 10E (Claim Releif Of Past Year Income Received Current Year)', 'price' => 520],
            ['id' => 223, 'category' => $cItr, 'name' => 'Form 15Cb (Form Payment Of Import To Non RESIdent Person)', 'price' => 899],
            ['id' => 224, 'category' => $cItr, 'name' => 'Form 15Cc (Form Payment Of Import To Non RESIdent Person)', 'price' => 899],
            ['id' => 225, 'category' => $cItr, 'name' => 'Tds Challan Submission', 'price' => 79],
            ['id' => 226, 'category' => $cItr, 'name' => 'Advance Tax Deposit (Quarterly)', 'price' => 219],
            ['id' => 227, 'category' => $cIso, 'name' => 'Iso Ce Certification', 'price' => 8449],
            ['id' => 228, 'category' => $cIso, 'name' => '13485:2016 Certification', 'price' => 8449],
            ['id' => 229, 'category' => $cIso, 'name' => '27001:2013 Certification', 'price' => 8449],
            ['id' => 230, 'category' => $cIso, 'name' => '14001:2015 Surveillance (1+2) (2 Term)', 'price' => 4439],
            ['id' => 231, 'category' => $cIso, 'name' => '14001:2015 Surveillance (1+1) (1 Term)', 'price' => 3469],
            ['id' => 232, 'category' => $cPf, 'name' => 'PF Surrender', 'price' => 849],
            ['id' => 233, 'category' => $cPf, 'name' => 'Only ESI Return Filing- From 51- 100 Employees', 'price' => 5329],
            ['id' => 234, 'category' => $cPf, 'name' => 'Only ESI Return Filing- From 21- 50 Employees', 'price' => 999],
            ['id' => 235, 'category' => $cPf, 'name' => 'Only ESI Return Filing- From 11- 20 Employees', 'price' => 599],
            ['id' => 236, 'category' => $cPf, 'name' => 'Only ESI Return Filing- Upto 10 Employees', 'price' => 369],
            ['id' => 237, 'category' => $cPf, 'name' => 'Only PF Return Filing- From 51- 100 Employees', 'price' => 5329],
            ['id' => 238, 'category' => $cPf, 'name' => 'Only PF Return Filing- From 21- 50 Employees', 'price' => 899],
            ['id' => 239, 'category' => $cPf, 'name' => 'Only PF Return Filing- From 11- 20 Employees', 'price' => 609],
            ['id' => 240, 'category' => $cPf, 'name' => 'Only PF Return Filing- Upto 10 Employees', 'price' => 369],
            ['id' => 241, 'category' => $cPf, 'name' => 'PF & ESI (Both) Return Filing - From 51- 100 Employees', 'price' => 10639],
            ['id' => 242, 'category' => $cPf, 'name' => 'PF & ESI (Both) Return Filing - From 21- 50 Employees', 'price' => 1899],
            ['id' => 243, 'category' => $cPf, 'name' => 'PF & ESI (Both) Return Filing - Upto 10 Employees', 'price' => 899],
            ['id' => 244, 'category' => $cPf, 'name' => 'PF & ESI (Both) Return Filing - From 11- 20 Employees', 'price' => 1199],
            ['id' => 245, 'category' => $cPf, 'name' => 'PF And ESI Registration', 'price' => 2510],
            ['id' => 246, 'category' => $cCma, 'name' => 'Project And Cma Report (Upto ₹ 2Lacs)', 'price' => 699],
            ['id' => 247, 'category' => $cCma, 'name' => 'Project And Cma Report (From ₹ 2 Lacs To Upto ₹ 5 Lacs', 'price' => 799],
            ['id' => 248, 'category' => $cCma, 'name' => 'Project And Cma Report (From ₹ 5 Lacs To Upto ₹ 10 Lacs)', 'price' => 1199],
            ['id' => 249, 'category' => $cCma, 'name' => 'Project And Cma Report (From ₹ 10 Lacs To Upto ₹ 20 Lacs)', 'price' => 1662],
            ['id' => 250, 'category' => $cCma, 'name' => 'Project And Cma Report (From ₹ 20 Lacs To Upto ₹ 30 Lacs)', 'price' => 1898],
            ['id' => 251, 'category' => $cTm, 'name' => 'Trademark Registration Without Dsc (For Single (1) Class) Only For Individual', 'price' => 799],
            ['id' => 252, 'category' => $cTm, 'name' => 'Trademark Registration Without Dsc (For Single (1) Class) For Other Than Individual', 'price' => 799],
            ['id' => 253, 'category' => $cLicence, 'name' => 'Labour License (Only Professional Fees)', 'price' => 1299],
            ['id' => 254, 'category' => $cLicence, 'name' => 'Professional Tax Registration (Only Professional Fees)', 'price' => 1199],
            ['id' => 255, 'category' => $cMsme, 'name' => 'Msme Or Udyami Certificate', 'price' => 310],
            ['id' => 256, 'category' => $cMsme, 'name' => 'Udyog Aadhar (Msme) Amendment', 'price' => 399],
            ['id' => 257, 'category' => $cPartnership, 'name' => 'Partnership Deed (Only Processing Fees)', 'price' => 1199],
            ['id' => 258, 'category' => $cStartup, 'name' => 'Startup India Registration', 'price' => 999],
            ['id' => 259, 'category' => $cItr, 'name' => 'Back ITR', 'price' => 500],
            ['id' => 261, 'category' => $cItr, 'name' => 'BS ITR4', 'price' => 100],
            ['id' => 262, 'category' => $cItr, 'name' => 'ITR - 4 ( TURNOVER UPTO$ 40L)', 'price' => 399],
        ];
    }
}
