#!/usr/bin/env python3
"""Master translation generator for all remaining languages.
Generates translations for: ru_RU, it_IT, ja, nl_NL, zh_CN, pl_PL, tr_TR, sv_SE, id_ID, ar
"""
import json
import os

LANG_DIR = 'translations'
LOCALES = ['ru_RU', 'it_IT', 'ja', 'nl_NL', 'zh_CN', 'pl_PL', 'tr_TR', 'sv_SE', 'id_ID', 'ar']

strings = json.load(open('all_strings.json'))

# Master translation dict: English -> {locale: translation}
# Format: T["English string"] = [ru, it, ja, nl, zh, pl, tr, sv, id, ar]
# Order: ru_RU=0, it_IT=1, ja=2, nl_NL=3, zh_CN=4, pl_PL=5, tr_TR=6, sv_SE=7, id_ID=8, ar=9
T = {}

def t(en, ru, it, ja, nl, zh, pl, tr, sv, id_, ar):
    T[en] = {'ru_RU':ru, 'it_IT':it, 'ja':ja, 'nl_NL':nl, 'zh_CN':zh, 'pl_PL':pl, 'tr_TR':tr, 'sv_SE':sv, 'id_ID':id_, 'ar':ar}

# === Numbers and code examples (keep as-is) ===
for s in ['0.01','0.05','0.10','0.50','.95','.99','1.00','$parent_description . " | " . $value','$value * 1.21']:
    t(s, s,s,s,s,s,s,s,s,s,s)

# === Short UI labels ===
t('-- Field --', '-- Поле --', '-- Campo --', '-- フィールド --', '-- Veld --', '-- 字段 --', '-- Pole --', '-- Alan --', '-- Fält --', '-- Bidang --', '-- حقل --')
t('-- No shipping class --', '-- Без класса доставки --', '-- Nessuna classe di spedizione --', '-- 配送クラスなし --', '-- Geen verzendklasse --', '-- 无运输类别 --', '-- Brak klasy wysyłki --', '-- Nakliye sınıfı yok --', '-- Ingen fraktklass --', '-- Tanpa kelas pengiriman --', '-- بدون فئة شحن --')
t('-- Select Column (optional) --', '-- Выберите столбец (необ.) --', '-- Seleziona Colonna (opzionale) --', '-- 列を選択（任意）--', '-- Selecteer Kolom (optioneel) --', '-- 选择列（可选）--', '-- Wybierz Kolumnę (opcjonalnie) --', '-- Sütun Seçin (isteğe bağlı) --', '-- Välj Kolumn (valfritt) --', '-- Pilih Kolom (opsional) --', '-- اختر العمود (اختياري) --')
t('-- Select Column --', '-- Выберите столбец --', '-- Seleziona Colonna --', '-- 列を選択 --', '-- Selecteer Kolom --', '-- 选择列 --', '-- Wybierz Kolumnę --', '-- Sütun Seçin --', '-- Välj Kolumn --', '-- Pilih Kolom --', '-- اختر العمود --')
t('-- Select Field --', '-- Выберите поле --', '-- Seleziona Campo --', '-- フィールドを選択 --', '-- Selecteer Veld --', '-- 选择字段 --', '-- Wybierz Pole --', '-- Alan Seçin --', '-- Välj Fält --', '-- Pilih Bidang --', '-- اختر الحقل --')
t('-- Select Image Field --', '-- Выберите поле изображения --', '-- Seleziona Campo Immagine --', '-- 画像フィールドを選択 --', '-- Selecteer Afbeeldingsveld --', '-- 选择图片字段 --', '-- Wybierz Pole Obrazu --', '-- Görsel Alanı Seçin --', '-- Välj Bildfält --', '-- Pilih Bidang Gambar --', '-- اختر حقل الصورة --')
t('-- Select Source --', '-- Выберите источник --', '-- Seleziona Sorgente --', '-- ソースを選択 --', '-- Selecteer Bron --', '-- 选择来源 --', '-- Wybierz Źródło --', '-- Kaynak Seçin --', '-- Välj Källa --', '-- Pilih Sumber --', '-- اختر المصدر --')
t('-- Select Source Field --', '-- Выберите поле источника --', '-- Seleziona Campo Sorgente --', '-- ソースフィールドを選択 --', '-- Selecteer Bronveld --', '-- 选择源字段 --', '-- Wybierz Pole Źródłowe --', '-- Kaynak Alanı Seçin --', '-- Välj Källfält --', '-- Pilih Bidang Sumber --', '-- اختر حقل المصدر --')
t('-- Select XML Field --', '-- Выберите XML поле --', '-- Seleziona Campo XML --', '-- XMLフィールドを選択 --', '-- Selecteer XML Veld --', '-- 选择XML字段 --', '-- Wybierz Pole XML --', '-- XML Alanı Seçin --', '-- Välj XML-fält --', '-- Pilih Bidang XML --', '-- اختر حقل XML --')
t('-- Select XML field with base price --', '-- Выберите XML поле с базовой ценой --', '-- Seleziona campo XML con prezzo base --', '-- 基本価格のXMLフィールドを選択 --', '-- Selecteer XML veld met basisprijs --', '-- 选择含基础价格的XML字段 --', '-- Wybierz pole XML z ceną bazową --', '-- Taban fiyatlı XML alanını seçin --', '-- Välj XML-fält med baspris --', '-- Pilih bidang XML dengan harga dasar --', '-- اختر حقل XML بالسعر الأساسي --')
t('-- Select template --', '-- Выберите шаблон --', '-- Seleziona modello --', '-- テンプレートを選択 --', '-- Selecteer sjabloon --', '-- 选择模板 --', '-- Wybierz szablon --', '-- Şablon seçin --', '-- Välj mall --', '-- Pilih template --', '-- اختر القالب --')

# Short single/double-word labels
t('Actions', 'Действия', 'Azioni', 'アクション', 'Acties', '操作', 'Akcje', 'İşlemler', 'Åtgärder', 'Tindakan', 'إجراءات')
t('Active', 'Активный', 'Attivo', 'アクティブ', 'Actief', '活跃', 'Aktywny', 'Aktif', 'Aktiv', 'Aktif', 'نشط')
t('Active Imports', 'Активные импорты', 'Importazioni Attive', 'アクティブなインポート', 'Actieve Imports', '活跃导入', 'Aktywne Importy', 'Aktif İçe Aktarımlar', 'Aktiva Importer', 'Impor Aktif', 'الاستيرادات النشطة')
t('Active Schedule', 'Активное расписание', 'Pianificazione Attiva', 'アクティブスケジュール', 'Actief Schema', '活跃计划', 'Aktywny Harmonogram', 'Aktif Zamanlama', 'Aktivt Schema', 'Jadwal Aktif', 'الجدول النشط')
t('Activate', 'Активировать', 'Attiva', '有効化', 'Activeren', '激活', 'Aktywuj', 'Etkinleştir', 'Aktivera', 'Aktifkan', 'تفعيل')
t('Add Attribute', 'Добавить атрибут', 'Aggiungi Attributo', '属性を追加', 'Attribuut Toevoegen', '添加属性', 'Dodaj Atrybut', 'Özellik Ekle', 'Lägg till Attribut', 'Tambah Atribut', 'إضافة سمة')
t('Add Condition', 'Добавить условие', 'Aggiungi Condizione', '条件を追加', 'Voorwaarde Toevoegen', '添加条件', 'Dodaj Warunek', 'Koşul Ekle', 'Lägg till Villkor', 'Tambah Kondisi', 'إضافة شرط')
t('Add Custom Field', 'Добавить поле', 'Aggiungi Campo', 'カスタムフィールドを追加', 'Aangepast Veld Toevoegen', '添加自定义字段', 'Dodaj Pole Niestandardowe', 'Özel Alan Ekle', 'Lägg till Anpassat Fält', 'Tambah Field Kustom', 'إضافة حقل مخصص')
t('Add Field', 'Добавить поле', 'Aggiungi Campo', 'フィールドを追加', 'Veld Toevoegen', '添加字段', 'Dodaj Pole', 'Alan Ekle', 'Lägg till Fält', 'Tambah Bidang', 'إضافة حقل')
t('Add Filter', 'Добавить фильтр', 'Aggiungi Filtro', 'フィルターを追加', 'Filter Toevoegen', '添加过滤器', 'Dodaj Filtr', 'Filtre Ekle', 'Lägg till Filter', 'Tambah Filter', 'إضافة فلتر')
t('Add Key-Value Pair', 'Добавить пару ключ-значение', 'Aggiungi Coppia Chiave-Valore', 'キー値ペアを追加', 'Sleutel-Waarde Paar Toevoegen', '添加键值对', 'Dodaj Parę Klucz-Wartość', 'Anahtar-Değer Çifti Ekle', 'Lägg till Nyckel-Värde Par', 'Tambah Pasangan Kunci-Nilai', 'إضافة زوج مفتاح-قيمة')
t('Add Meta Field', 'Добавить мета-поле', 'Aggiungi Campo Meta', 'メタフィールドを追加', 'Metaveld Toevoegen', '添加元字段', 'Dodaj Pole Meta', 'Meta Alan Ekle', 'Lägg till Metafält', 'Tambah Field Meta', 'إضافة حقل ميتا')
t('Add Rule', 'Добавить правило', 'Aggiungi Regola', 'ルールを追加', 'Regel Toevoegen', '添加规则', 'Dodaj Regułę', 'Kural Ekle', 'Lägg till Regel', 'Tambah Aturan', 'إضافة قاعدة')
t('Add Shipping Rule', 'Добавить правило доставки', 'Aggiungi Regola di Spedizione', '配送ルールを追加', 'Verzendregel Toevoegen', '添加运输规则', 'Dodaj Regułę Wysyłki', 'Kargo Kuralı Ekle', 'Lägg till Fraktregel', 'Tambah Aturan Pengiriman', 'إضافة قاعدة شحن')
t('Advanced', 'Расширенные', 'Avanzate', '詳細', 'Geavanceerd', '高级', 'Zaawansowane', 'Gelişmiş', 'Avancerat', 'Lanjutan', 'متقدم')
t('Advanced Fields', 'Расширенные поля', 'Campi Avanzati', '高度なフィールド', 'Geavanceerde Velden', '高级字段', 'Zaawansowane Pola', 'Gelişmiş Alanlar', 'Avancerade Fält', 'Bidang Lanjutan', 'حقول متقدمة')
t('Advanced Formulas', 'Расширенные формулы', 'Formule Avanzate', '高度な数式', 'Geavanceerde Formules', '高级公式', 'Zaawansowane Formuły', 'Gelişmiş Formüller', 'Avancerade Formler', 'Rumus Lanjutan', 'صيغ متقدمة')
t('Advanced Import Options', 'Расширенные параметры импорта', 'Opzioni di Importazione Avanzate', '高度なインポートオプション', 'Geavanceerde Importopties', '高级导入选项', 'Zaawansowane Opcje Importu', 'Gelişmiş İçe Aktarma Seçenekleri', 'Avancerade Importalternativ', 'Opsi Impor Lanjutan', 'خيارات استيراد متقدمة')
t('Advanced Options', 'Расширенные опции', 'Opzioni Avanzate', '高度なオプション', 'Geavanceerde Opties', '高级选项', 'Zaawansowane Opcje', 'Gelişmiş Seçenekler', 'Avancerade Alternativ', 'Opsi Lanjutan', 'خيارات متقدمة')
t('Advanced Processing', 'Расширенная обработка', 'Elaborazione Avanzata', '高度な処理', 'Geavanceerde Verwerking', '高级处理', 'Zaawansowane Przetwarzanie', 'Gelişmiş İşleme', 'Avancerad Bearbetning', 'Pemrosesan Lanjutan', 'معالجة متقدمة')
t('Advanced Settings', 'Расширенные настройки', 'Impostazioni Avanzate', '高度な設定', 'Geavanceerde Instellingen', '高级设置', 'Zaawansowane Ustawienia', 'Gelişmiş Ayarlar', 'Avancerade Inställningar', 'Pengaturan Lanjutan', 'إعدادات متقدمة')

# Save intermediate progress and continue loading from part files
# This file is continued in gen_all_part2.py, gen_all_part3.py, etc.

# Now process: load part files if they exist
for part in range(2, 20):
    partfile = f'gen_all_part{part}.py'
    if os.path.exists(partfile):
        exec(open(partfile).read())

# Apply translations to all locale files
for locale in LOCALES:
    filepath = os.path.join(LANG_DIR, f'{locale}.json')
    try:
        lang = json.load(open(filepath, encoding='utf-8'))
    except:
        lang = {}
    
    added = 0
    for en, translations in T.items():
        if locale in translations and en not in lang:
            lang[en] = translations[locale]
            added += 1
    
    with open(filepath, 'w', encoding='utf-8') as f:
        json.dump(lang, f, ensure_ascii=False, indent=2)
    
    covered = sum(1 for s in strings if s in lang)
    print(f"{locale}: {len(lang)} entries, coverage: {covered}/{len(strings)}, added: {added}")
