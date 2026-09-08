<?php

namespace App\Services;

use App\Models\QuranAyahPage;
use InvalidArgumentException;

class QuranPageService
{
    /**
     * عدد صفحات المصحف المدني.
     */
    private const TOTAL_PAGES = 604;

    /**
     * عدد السور في القرآن.
     */
    private const TOTAL_SURAHS = 114;

    /**
     * حساب عدد الصفحات بين آيتين.
     *
     * الحساب شامل صفحة البداية وصفحة النهاية.
     *
     * مثال:
     * الصفحة 10 إلى الصفحة 10 = صفحة واحدة
     * الصفحة 10 إلى الصفحة 15 = 6 صفحات
     *
     * @param string|int $fromSurah
     * @param int        $fromAyah
     * @param string|int $toSurah
     * @param int        $toAyah
     *
     * @return int
     */
    public function calculatePages(
        string|int $fromSurah,
        int $fromAyah,
        string|int $toSurah,
        int $toAyah
    ): int {
        // ---------------------------------------------------------
        // 1. تحويل أسماء السور إلى أرقام
        // ---------------------------------------------------------

        $fromSurahNumber = $this->getSurahNumber($fromSurah);
        $toSurahNumber   = $this->getSurahNumber($toSurah);

        // ---------------------------------------------------------
        // 2. التحقق من أرقام الآيات
        // ---------------------------------------------------------

        $this->validateAyahNumber($fromAyah, 'بداية الحفظ');
        $this->validateAyahNumber($toAyah, 'نهاية الحفظ');

        // ---------------------------------------------------------
        // 3. التحقق من ترتيب البداية والنهاية
        //
        // القرآن مرتب:
        // السورة 1 ثم 2 ثم 3 ...
        // وداخل كل سورة الآيات 1 ثم 2 ثم 3 ...
        // ---------------------------------------------------------

        if (
            $fromSurahNumber > $toSurahNumber ||
            (
                $fromSurahNumber === $toSurahNumber &&
                $fromAyah > $toAyah
            )
        ) {
            throw new InvalidArgumentException(
                'بداية الحفظ يجب أن تكون قبل نهاية الحفظ'
            );
        }

        // ---------------------------------------------------------
        // 4. الحصول على بيانات آية البداية
        // ---------------------------------------------------------

        $fromRecord = $this->findAyah(
            $fromSurahNumber,
            $fromAyah,
            'بداية الحفظ'
        );

        // ---------------------------------------------------------
        // 5. الحصول على بيانات آية النهاية
        // ---------------------------------------------------------

        $toRecord = $this->findAyah(
            $toSurahNumber,
            $toAyah,
            'نهاية الحفظ'
        );

        // ---------------------------------------------------------
        // 6. التحقق من أرقام الصفحات
        // ---------------------------------------------------------

        $fromPage = (int) $fromRecord->page_number;
        $toPage   = (int) $toRecord->page_number;

        $this->validatePageNumber(
            $fromPage,
            'صفحة بداية الحفظ'
        );

        $this->validatePageNumber(
            $toPage,
            'صفحة نهاية الحفظ'
        );

        // ---------------------------------------------------------
        // 7. التأكد من أن نهاية الحفظ ليست في صفحة قبل البداية
        //
        // هذا يحميك أيضاً من وجود خطأ في بيانات جدول القرآن.
        // ---------------------------------------------------------

        if ($fromPage > $toPage) {
            throw new InvalidArgumentException(
                'بيانات صفحات القرآن غير صحيحة: صفحة النهاية قبل صفحة البداية'
            );
        }

        // ---------------------------------------------------------
        // 8. حساب عدد الصفحات
        //
        // +1 لأن الحساب شامل الطرفين.
        //
        // مثال:
        // من صفحة 10 إلى 15:
        //
        // 15 - 10 + 1 = 6
        // ---------------------------------------------------------

        return ($toPage - $fromPage) + 1;
    }

    /**
     * حساب عدد الأوراق بناءً على عدد الصفحات.
     *
     * نفترض أن كل ورقة تحتوي على صفحتين.
     *
     * مثال:
     *
     * 1 صفحة  = 1 ورقة
     * 2 صفحات = 1 ورقة
     * 3 صفحات = 2 ورقة
     * 4 صفحات = 2 ورقة
     * 5 صفحات = 3 ورقة
     */
    public function calculateSheets(int $numberOfPages): int
    {
        if ($numberOfPages < 1) {
            throw new InvalidArgumentException(
                'عدد الصفحات يجب أن يكون أكبر من صفر'
            );
        }

        return (int) ceil($numberOfPages / 2);
    }

    /**
     * حساب الصفحات والأوراق معاً.
     *
     * يرجع:
     *
     * [
     *     'pages' => 10,
     *     'sheets' => 5,
     * ]
     */
    public function calculatePagesAndSheets(
        string|int $fromSurah,
        int $fromAyah,
        string|int $toSurah,
        int $toAyah
    ): array {
        $pages = $this->calculatePages(
            $fromSurah,
            $fromAyah,
            $toSurah,
            $toAyah
        );

        $sheets = $this->calculateSheets($pages);

        return [
            'pages' => $pages,
            'sheets' => $sheets,
        ];
    }

    /**
     * البحث عن آية داخل جدول quran_ayah_pages.
     */
    private function findAyah(
        int $surahNumber,
        int $ayahNumber,
        string $position
    ): QuranAyahPage {
        $record = QuranAyahPage::query()
            ->where('surah_number', $surahNumber)
            ->where('ayah_number', $ayahNumber)
            ->first();

        if (!$record) {
            throw new InvalidArgumentException(
                "{$position} غير صحيحة: السورة {$surahNumber} - الآية {$ayahNumber}"
            );
        }

        return $record;
    }

    /**
     * التحقق من رقم الآية.
     *
     * أرقام الآيات تبدأ من 1.
     */
    private function validateAyahNumber(
        int $ayahNumber,
        string $position
    ): void {
        if ($ayahNumber < 1) {
            throw new InvalidArgumentException(
                "رقم آية {$position} يجب أن يكون أكبر من صفر"
            );
        }
    }

    /**
     * التحقق من رقم الصفحة.
     *
     * المصحف المدني يحتوي على 604 صفحات.
     */
    private function validatePageNumber(
        int $pageNumber,
        string $position
    ): void {
        if (
            $pageNumber < 1 ||
            $pageNumber > self::TOTAL_PAGES
        ) {
            throw new InvalidArgumentException(
                "{$position} غير صحيحة: رقم الصفحة {$pageNumber}"
            );
        }
    }

    /**
     * تحويل اسم السورة أو رقمها إلى رقم السورة.
     *
     * يقبل:
     *
     * 2
     * "2"
     * "البقرة"
     * " آل عمران "
     */
    private function getSurahNumber(string|int $surah): int
    {
        // ---------------------------------------------------------
        // إذا تم إرسال رقم السورة
        // ---------------------------------------------------------

        if (is_numeric($surah)) {
            $number = (int) $surah;

            if (
                $number < 1 ||
                $number > self::TOTAL_SURAHS
            ) {
                throw new InvalidArgumentException(
                    "رقم السورة غير صحيح: {$surah}"
                );
            }

            return $number;
        }

        // ---------------------------------------------------------
        // تنظيف اسم السورة
        // ---------------------------------------------------------

        $surah = trim($surah);

        if ($surah === '') {
            throw new InvalidArgumentException(
                'اسم السورة مطلوب'
            );
        }

        // ---------------------------------------------------------
        // أسماء السور
        // ---------------------------------------------------------

        $surahs = [
            'الفاتحة' => 1,
            'البقرة' => 2,
            'آل عمران' => 3,
            'النساء' => 4,
            'المائدة' => 5,
            'الأنعام' => 6,
            'الأعراف' => 7,
            'الأنفال' => 8,
            'التوبة' => 9,
            'يونس' => 10,
            'هود' => 11,
            'يوسف' => 12,
            'الرعد' => 13,
            'إبراهيم' => 14,
            'الحجر' => 15,
            'النحل' => 16,
            'الإسراء' => 17,
            'الكهف' => 18,
            'مريم' => 19,
            'طه' => 20,
            'الأنبياء' => 21,
            'الحج' => 22,
            'المؤمنون' => 23,
            'النور' => 24,
            'الفرقان' => 25,
            'الشعراء' => 26,
            'النمل' => 27,
            'القصص' => 28,
            'العنكبوت' => 29,
            'الروم' => 30,
            'لقمان' => 31,
            'السجدة' => 32,
            'الأحزاب' => 33,
            'سبأ' => 34,
            'فاطر' => 35,
            'يس' => 36,
            'الصافات' => 37,
            'ص' => 38,
            'الزمر' => 39,
            'غافر' => 40,
            'فصلت' => 41,
            'الشورى' => 42,
            'الزخرف' => 43,
            'الدخان' => 44,
            'الجاثية' => 45,
            'الأحقاف' => 46,
            'محمد' => 47,
            'الفتح' => 48,
            'الحجرات' => 49,
            'ق' => 50,
            'الذاريات' => 51,
            'الطور' => 52,
            'النجم' => 53,
            'القمر' => 54,
            'الرحمن' => 55,
            'الواقعة' => 56,
            'الحديد' => 57,
            'المجادلة' => 58,
            'الحشر' => 59,
            'الممتحنة' => 60,
            'الصف' => 61,
            'الجمعة' => 62,
            'المنافقون' => 63,
            'التغابن' => 64,
            'الطلاق' => 65,
            'التحريم' => 66,
            'الملك' => 67,
            'القلم' => 68,
            'الحاقة' => 69,
            'المعارج' => 70,
            'نوح' => 71,
            'الجن' => 72,
            'المزمل' => 73,
            'المدثر' => 74,
            'القيامة' => 75,
            'الإنسان' => 76,
            'المرسلات' => 77,
            'النبأ' => 78,
            'النازعات' => 79,
            'عبس' => 80,
            'التكوير' => 81,
            'الانفطار' => 82,
            'المطففين' => 83,
            'الانشقاق' => 84,
            'البروج' => 85,
            'الطارق' => 86,
            'الأعلى' => 87,
            'الغاشية' => 88,
            'الفجر' => 89,
            'البلد' => 90,
            'الشمس' => 91,
            'الليل' => 92,
            'الضحى' => 93,
            'الشرح' => 94,
            'التين' => 95,
            'العلق' => 96,
            'القدر' => 97,
            'البينة' => 98,
            'الزلزلة' => 99,
            'العاديات' => 100,
            'القارعة' => 101,
            'التكاثر' => 102,
            'العصر' => 103,
            'الهمزة' => 104,
            'الفيل' => 105,
            'قريش' => 106,
            'الماعون' => 107,
            'الكوثر' => 108,
            'الكافرون' => 109,
            'النصر' => 110,
            'المسد' => 111,
            'الإخلاص' => 112,
            'الفلق' => 113,
            'الناس' => 114,
        ];

        if (!isset($surahs[$surah])) {
            throw new InvalidArgumentException(
                "اسم السورة غير صحيح: {$surah}"
            );
        }

        return $surahs[$surah];
    }
}

