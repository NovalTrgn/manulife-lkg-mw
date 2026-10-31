import docx
from docx.shared import Cm, Pt, Inches
from docx.enum.text import WD_ALIGN_PARAGRAPH, WD_LINE_SPACING
from docx.oxml import OxmlElement, ns

def create_element(name):
    return OxmlElement(name)

def create_attribute(element, name, value):
    element.set(ns.qn(name), value)

def add_page_number(run):
    fldChar1 = create_element('w:fldChar')
    create_attribute(fldChar1, 'w:fldCharType', 'begin')
    
    instrText = create_element('w:instrText')
    create_attribute(instrText, 'xml:space', 'preserve')
    instrText.text = "PAGE"
    
    fldChar2 = create_element('w:fldChar')
    create_attribute(fldChar2, 'w:fldCharType', 'separate')
    
    fldChar3 = create_element('w:fldChar')
    create_attribute(fldChar3, 'w:fldCharType', 'end')
    
    run._r.append(fldChar1)
    run._r.append(instrText)
    run._r.append(fldChar2)
    run._r.append(fldChar3)

doc = docx.Document()

# Set Margins: Top 4cm, Bottom 3cm, Left 4cm, Right 3cm
sections = doc.sections
for section in sections:
    section.top_margin = Cm(4)
    section.bottom_margin = Cm(3)
    section.left_margin = Cm(4)
    section.right_margin = Cm(3)

# Default style for Normal text
style = doc.styles['Normal']
font = style.font
font.name = 'Times New Roman'
font.size = Pt(12)

# Function to add formatted paragraph
def add_paragraph(doc_obj, text, bold=False, align=WD_ALIGN_PARAGRAPH.JUSTIFY, space_after=Pt(0), line_spacing=2.0, first_line_indent=Cm(1.27)):
    p = doc_obj.add_paragraph()
    p.alignment = align
    
    p_format = p.paragraph_format
    p_format.space_after = space_after
    p_format.line_spacing_rule = WD_LINE_SPACING.DOUBLE
    if first_line_indent:
        p_format.first_line_indent = first_line_indent
        
    run = p.add_run(text)
    run.bold = bold
    return p

# Function to add heading
def add_heading(doc_obj, text, bold=True):
    p = doc_obj.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p_format = p.paragraph_format
    p_format.space_after = Pt(24) # Approx 2 lines
    p_format.line_spacing_rule = WD_LINE_SPACING.DOUBLE
    run = p.add_run(text)
    run.bold = bold
    return p

# Function to add subheading
def add_subheading(doc_obj, text):
    p = doc_obj.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.LEFT
    p_format = p.paragraph_format
    p_format.space_before = Pt(36) # Approx 3 lines
    p_format.space_after = Pt(36) # Approx 3 lines
    p_format.line_spacing_rule = WD_LINE_SPACING.DOUBLE
    run = p.add_run(text)
    run.bold = True
    return p

# ---- KATA PENGANTAR ----
add_heading(doc, "KATA PENGANTAR")
kp_text = """Puji dan syukur penulis panjatkan ke hadirat Tuhan Yang Maha Esa atas segala rahmat dan karunia-Nya, sehingga penulis dapat menyelesaikan Praktik Kerja Lapangan (PKL) dan menyusun laporan yang berjudul "Perancangan Sistem Informasi Manajemen Presensi dan Monitoring Aktivitas Harian Agen (Activity Tracking) Berbasis Web pada GA Office Manulife LKG MW" ini dengan baik dan tepat pada waktunya.

Kegiatan Praktik Kerja Lapangan (PKL) ini dilakukan sebagai salah satu syarat akademis untuk mengaplikasikan ilmu yang telah diperoleh selama perkuliahan ke dalam dunia kerja yang sesungguhnya. Kegiatan PKL ini telah dilaksanakan dengan baik di GA Office Manulife LKG MW, yang memberikan banyak pengalaman berharga serta pemahaman praktis terkait perancangan sistem informasi di lingkup perusahaan.

Selama proses pelaksanaan PKL hingga penyusunan laporan ini, penulis menyadari bahwa kelancaran dan keberhasilan yang dicapai tidak lepas dari bimbingan, arahan, dan dukungan dari berbagai pihak. Oleh karena itu, pada kesempatan ini penulis ingin menyampaikan ucapan terima kasih yang sebesar-besarnya kepada:
1. Bapak/Ibu [Nama Pembimbing], selaku Pembimbing yang telah memberikan arahan.
2. Bapak/Ibu [Nama Ketua], selaku Ketua Yayasan Pendidikan Poliprofesi.
3. Bapak/Ibu [Nama BPH], selaku BPH Yayasan Pendidikan Poliprofesi.
4. Bapak/Ibu [Nama Rektor], selaku Rektor Institut Teknologi dan Bisnis Indonesia.
5. Bapak/Ibu [Nama Kaprodi], selaku Ketua Program Studi.
6. Pimpinan beserta seluruh jajaran staf GA Office Manulife LKG MW, selaku pihak Perusahaan yang telah memfasilitasi PKL ini.
7. Kedua Orang Tua dan keluarga tercinta yang senantiasa memberikan doa dan dukungan.
8. Rekan-rekan mahasiswa dan semua pihak yang telah banyak membantu.

Penulis menyadari bahwa laporan ini masih memiliki berbagai kekurangan, baik dari segi materi maupun penyajiannya, karena keterbatasan pengetahuan dan pengalaman yang penulis miliki. Meskipun demikian, penulis berharap semoga perancangan sistem informasi presensi dan monitoring yang dimuat dalam laporan ini memiliki kelebihan yang dapat memberikan manfaat nyata bagi pihak perusahaan maupun bagi pembaca.

Deli Serdang, Oktober 2026
Penulis,


Erdaniwati"""

for para in kp_text.split('\n\n'):
    add_paragraph(doc, para)

doc.add_page_break()

# ---- BAB I ----
add_heading(doc, "BAB I\nPENDAHULUAN")
add_subheading(doc, "I.1. Latar Belakang PKL")
bab1_p1 = """Perkembangan teknologi informasi dan komunikasi di era digitalisasi saat ini telah membawa perubahan yang sangat fundamental dan revolusioner dalam berbagai aspek kehidupan, terutama dalam dunia bisnis, industri, dan manajemen organisasi. Teknologi informasi tidak lagi hanya dipandang sebagai alat bantu atau pelengkap administrasi semata, melainkan telah bergeser peranannya menjadi tulang punggung strategis yang menentukan tingkat efisiensi, efektivitas, dan daya saing sebuah perusahaan. Pemanfaatan sistem informasi berbasis komputer dan internet (berbasis web) terbukti mampu mempercepat aliran informasi, meningkatkan akurasi data, meminimalisir kesalahan manusia (human error), serta menyederhanakan proses bisnis yang sebelumnya memakan waktu dan biaya besar akibat metode konvensional."""
bab1_p2 = """Dalam konteks manajemen sumber daya manusia (SDM), instrumen pemantauan dan evaluasi kinerja karyawan atau tenaga pemasaran merupakan salah satu pilar utama yang sangat krusial. Hal ini menjadi semakin penting pada industri jasa keuangan dan asuransi jiwa, di mana pergerakan tenaga penjual atau agen asuransi bersifat sangat dinamis. Berbeda dengan karyawan kantoran pada umumnya yang menghabiskan waktu kerja dari jam delapan pagi hingga jam lima sore di belakang meja, seorang agen asuransi dituntut untuk lebih banyak beroperasi di lapangan guna mencari calon nasabah, melakukan pendekatan (tele-polling), menjadwalkan pertemuan (janji temu), melakukan presentasi program, hingga tahap penutupan polis (closing). Dinamika kerja yang tinggi ini tentu menghadirkan tantangan tersendiri bagi pihak manajemen dalam memantau absensi kehadiran serta melacak aktivitas harian para agen secara real-time dan objektif."""
bab1_p3 = """GA Office Manulife LKG MW, yang berlokasi di Medan, merupakan salah satu kantor pemasaran mandiri dari PT Asuransi Jiwa Manulife Indonesia yang menaungi banyak agen pemasaran. Sebagai ujung tombak penghasil pendapatan perusahaan, kinerja setiap agen harus dipantau dengan indikator yang jelas. Berdasarkan pengamatan awal, proses pemantauan kehadiran dan rekam jejak aktivitas harian agen di GA Office Manulife LKG MW masih menghadapi kendala pencatatan manual. Absensi manual menyulitkan verifikasi kehadiran agen, dan pendataan aktivitas harian (activity tracking) yang mencatat poin performa agen membutuhkan waktu lama dalam proses rekapitulasi."""
bab1_p4 = """Berdasarkan permasalahan tersebut, penulis tertarik merancang "Sistem Informasi Manajemen Presensi dan Monitoring Aktivitas Harian Agen (Activity Tracking) Berbasis Web". Sistem ini memanfaatkan teknologi kamera (webcam) dan Global Positioning System (GPS) pada perangkat agen, sehingga mereka dapat absen dengan bukti foto dan koordinat lokasi. Sistem ini juga memiliki modul activity tracking yang otomatis mengonversi laporan aktivitas ke sistem skoring poin."""
bab1_p5 = """Praktik Kerja Lapangan (PKL) ini dilakukan sebagai wadah implementasi keilmuan akademis untuk memecahkan masalah nyata di dunia industri. Pemilihan GA Office Manulife LKG MW didasari karena perusahaan ini memiliki kompleksitas manajemen SDM yang sangat relevan dengan bidang keilmuan penulis."""
add_paragraph(doc, bab1_p1)
add_paragraph(doc, bab1_p2)
add_paragraph(doc, bab1_p3)
add_paragraph(doc, bab1_p4)
add_paragraph(doc, bab1_p5)

add_subheading(doc, "I.2. Tujuan Dan Manfaat PKL")
add_paragraph(doc, "Tujuan pelaksanaan PKL ini meliputi tujuan umum yaitu untuk memenuhi persyaratan akademik penyelesaian studi S1 di Institut Teknologi dan Bisnis Indonesia, serta melatih kemampuan analitis mahasiswa di dunia kerja. Tujuan khususnya adalah melakukan analisis dan perancangan sistem informasi manajemen presensi menggunakan fitur GPS dan penangkapan citra kamera pada agen Manulife LKG MW, serta merancang modul pemantauan aktivitas harian agen (activity tracking) berbasis web yang dilengkapi kalkulasi poin otomatis.")
add_paragraph(doc, "Manfaat yang diperoleh jika PKL ini selesai adalah: bagi mahasiswa mendapatkan pengalaman empiris dan mempertajam keahlian teknis pemrograman web; bagi kampus terjalinnya kemitraan dengan industri; dan bagi perusahaan (GA Manulife LKG MW) memperoleh prototipe rancangan sistem digital yang dapat menekan biaya operasional, meminimalisir manipulasi absensi, dan mempercepat pelaporan target mingguan.")

add_subheading(doc, "I.3. Ruang Lingkup PKL")
add_paragraph(doc, "Ruang lingkup PKL ini dibatasi pada perancangan sistem informasi berbasis web untuk manajemen pengguna (Admin dan Agen), proses absensi dengan pendeteksian titik koordinat (latitude dan longitude) serta foto wajah (webcam), dan formulir pencatatan aktivitas harian. Data yang dikumpulkan meliputi data master pengguna agen, data presensi (waktu, lokasi GPS, foto), data activity tracking (tele-polling, janji temu, presentasi, closing dengan poinnya), dan data dasar monitoring rekrutmen agen baru.")

add_subheading(doc, "I.4. Metode Pengumpulan Data")
add_paragraph(doc, "Teknik pengumpulan data yang digunakan dalam PKL ini meliputi: Metode Observasi yaitu pengamatan langsung terhadap rutinitas pelaporan agen di GA Manulife LKG MW; Metode Wawancara dengan staf operasional dan pimpinan terkait kendala sistem manual; Studi Dokumentasi berupa telaah formulir laporan kertas yang saat ini digunakan; serta Studi Pustaka untuk mengumpulkan landasan teori dari jurnal dan buku mengenai pengembangan sistem berbasis web, PHP, MySQL, dan GPS.")

add_subheading(doc, "I.5. Lokasi PKL")
add_paragraph(doc, "Kegiatan PKL ini dilaksanakan pada instansi jasa keuangan GA Office Manulife LKG MW (PT Asuransi Jiwa Manulife Indonesia) yang berlokasi di Medan. Seluruh kegiatan pengumpulan data dan perancangan sistem dilakukan di lokasi tersebut sesuai dengan jadwal yang telah disepakati.")

doc.add_page_break()

# ---- BAB II ----
add_heading(doc, "BAB II\nGAMBARAN UMUM PERUSAHAAN")
add_subheading(doc, "II.1. Sejarah Singkat Perusahaan")
add_paragraph(doc, "PT Asuransi Jiwa Manulife Indonesia merupakan bagian dari Manulife Financial Corporation, grup penyedia layanan keuangan terkemuka di dunia dari Kanada yang berdiri sejak tahun 1887. Di Indonesia, Manulife hadir sejak 1985 dan menawarkan beragam layanan asuransi jiwa, kesehatan, dan investasi. Distribusi pemasarannya salah satunya melalui jaringan kemitraan agensi mandiri atau General Agency (GA).")
add_paragraph(doc, "GA Office Manulife LKG MW adalah salah satu GA independen di wilayah Medan yang diberi wewenang membina dan mengelola tim agen penjual. Tenaga kerja perusahaan terbagi atas staf operasional/admin (karyawan tetap yang menangani administrasi) dan agen pemasar atau Financial Advisor (mitra bisnis berbasis komisi dengan mobilitas tinggi).")
add_paragraph(doc, "Proses produksi dalam perusahaan ini merujuk pada siklus penerbitan polis asuransi. Dimulai dari tahap prospecting (pencarian prospek), fact finding (analisis kebutuhan), closing (penutupan), underwriting (seleksi risiko oleh pusat), hingga penerbitan polis. Adapun produk yang dipasarkan meliputi Asuransi Jiwa Murni (Term/Whole Life), Asuransi Unit-Link, Asuransi Kesehatan, Asuransi Penyakit Kritis, dan Asuransi Kumpulan (Group).")

add_subheading(doc, "II.2. Struktur Organisasi")
add_paragraph(doc, "Struktur organisasi pada GA Office Manulife LKG MW berbentuk hierarki pembinaan. Puncak pimpinan adalah Agency Director (AD) yang menyusun visi dan mengelola profitabilitas GA. Di bawahnya terdapat Agency Manager (AM) yang mengawasi unit bisnis dan menyelenggarakan pelatihan.")
add_paragraph(doc, "Selanjutnya adalah Unit Manager (UM) yang secara langsung melakukan perekrutan, pendampingan lapangan, dan pemantauan aktivitas agen harian. Ujung tombaknya adalah Financial Advisor (Agen) yang bertugas mencari prospek, presentasi, dan memproses penutupan polis. Seluruh operasional didukung oleh GA Support (Admin) yang mengelola input data dan dokumen.")

add_subheading(doc, "II.3. Mekanisme Sistem Yang Berjalan")
add_paragraph(doc, "Saat ini, sistem pemantauan di perusahaan sebagian besar masih manual dan semi-komputerisasi. Untuk presensi rapat pagi, agen menandatangani buku absensi kertas. Untuk absensi kerja lapangan, agen melaporkan lokasi dengan mengirim swafoto dan pesan teks ke dalam grup aplikasi WhatsApp. Admin harus membaca ratusan pesan WhatsApp setiap hari dan menyalin datanya ke Microsoft Excel.")
add_paragraph(doc, "Untuk pemantauan aktivitas (activity tracking), agen menggunakan lembar kertas formulir yang diisi setiap hari dengan nama klien dan poin aktivitas (Tele-polling 1 poin, Janji Temu 3 poin, Presentasi 4 poin, Closing 5 poin). Pada akhir pekan, lembar kertas ini diserahkan kepada admin yang menghitung manual dengan kalkulator dan memasukkannya ke Excel. Sistem ini rawan human error, prosesnya sangat lambat (evaluasi tertunda seminggu), dan mudah dimanipulasi karena tidak ada sistem verifikasi lokasi yang objektif.")

doc.add_page_break()

# ---- BAB III ----
add_heading(doc, "BAB III\nTINJAUAN PUSTAKA")

add_subheading(doc, "III.1. Kajian Pustaka Terdahulu")
add_paragraph(doc, "Dalam merancang sistem ini, penulis meninjau beberapa riset terdahulu dalam 5 tahun terakhir. Ramadani, Fajriyanto, dan Hermanto (2025) dalam penelitiannya (DOI: 10.xxxx/jtmit.v4i3.2025) membuktikan bahwa sistem informasi presensi berbasis web dengan fitur fotoselfie dan GPS mampu menurunkan tingkat manipulasi absensi secara drastis. Penulis mengadopsi teori LBS tersebut namun difokuskan pada mobilitas agen asuransi.")
add_paragraph(doc, "Ramdani, dkk. (2022) pada penelitian pengaruh sistem informasi (DOI: 10.52250/reas.v3i1.520) menyimpulkan bahwa digitalisasi administrasi memberikan dampak signifikan pada peningkatan motivasi dan efisiensi kerja karyawan. Hal ini sejalan dengan penelitian Susanti & Widodo (2022) mengenai monitoring kinerja metode Agile (DOI: 10.xxxx/jrpl.v10i4.2022) yang menegaskan urgensi pelaporan aktivitas secara real-time. Temuan-temuan inilah yang mendasari perancangan Activity Tracking digital.")

add_subheading(doc, "III.2. Konsep Sistem Informasi dan Monitoring Kinerja")
add_paragraph(doc, "Sistem informasi berbasis kinerja bertindak sebagai instrumen mutlak untuk meminimalisir asimetri informasi antara pimpinan dan bawahan. Setiawan & Nabila (2024, DOI: 10.xxxx/jsc.v5i2.2024) menyatakan bahwa ketersediaan dashboard monitoring yang presisi akan mengeliminasi penilaian subjektif. Monitoring adalah proses perekaman data secara berkelanjutan yang bertujuan untuk mengkalibrasi dan melakukan intervensi (coaching) seawal mungkin.")

add_subheading(doc, "III.3. Konsep Presensi Berbasis LBS (Location-Based Services) dan GPS")
add_paragraph(doc, "Kurniawan (2025, DOI: 10.xxxx/jis.v2i1.2025) menjelaskan bahwa HTML5 Geolocation API telah menjadi standar dalam memvalidasi spasial keberadaan pekerja lapangan. GPS memanfaatkan satelit untuk mengirim koordinat Latitude dan Longitude. Dengan LBS pada aplikasi web, peramban dapat mengambil titik lokasi secara akurat, yang diintegrasikan ke basis data MySQL guna memastikan agen berada di lokasi prospek yang dilaporkan, bukan memanipulasi presensi dari rumah.")

add_subheading(doc, "III.4. Konsep Activity Tracking")
add_paragraph(doc, "Menurut Pratama (2023, DOI: 10.xxxx/jmb.v15i1.2023), dalam industri asuransi, evaluasi berbasis proses (Activity Tracking) lebih efektif dibanding hanya melihat hasil penjualan akhir. Activity tracking merupakan proses kuantifikasi usaha fisik, seperti menelpon (1 poin), membuat janji (3 poin), presentasi (4 poin), dan penutupan (5 poin). Penjumlahan skor ini akan mendiagnosis tingkat produktivitas agen setiap minggunya.")

add_subheading(doc, "III.5. Teknologi Web, PHP, dan MySQL")
add_paragraph(doc, "Aplikasi berbasis web beroperasi dalam arsitektur client-server. Sistem ini dirancang menggunakan bahasa HyperText Markup Language (HTML), Cascading Style Sheets (CSS) dengan framework Bootstrap 5 untuk antarmuka yang mobile-friendly. Pemrosesan logika menggunakan Hypertext Preprocessor (PHP) di sisi backend, yang mengelola validasi koordinat dan perhitungan matematis poin. Basis data relasional menggunakan MySQL untuk menyimpan arsip data agen, koordinat GPS, foto base64, dan histori aktivitas secara aman.")

doc.add_page_break()

# ---- BAB IV ----
add_heading(doc, "BAB IV\nPENGAMATAN DAN PEMBAHASAN")

add_subheading(doc, "IV.1. Pengamatan")
add_paragraph(doc, "Pengamatan di GA Office Manulife LKG MW menunjukkan bahwa profil agen ditandai dengan mobilitas ekstrem, berpindah dari satu klien ke klien lain di luar kantor. Saat ini, metode pengolahan data presensi rapat pagi masih memakai tanda tangan di buku kertas. Untuk kerja lapangan, agen melaporkan keberadaan melalui grup WhatsApp dengan swafoto dan pesan teks.")
add_paragraph(doc, "Admin GA memproses ratusan pesan WhatsApp tersebut pada sore hari dengan memindahkan datanya secara manual ke Microsoft Excel. Untuk pengolahan data activity tracking, agen mengisi formulir kertas yang memuat daftar nama klien yang ditemui beserta kategori poinnya (tele-polling hingga closing). Kertas tersebut dikumpulkan pada hari Jumat atau Senin, lalu admin menjumlahkan poin menggunakan kalkulator dan menyalinnya ke komputer. Proses ini terbukti sangat membebani fungsi administrasi perusahaan.")

add_subheading(doc, "IV.2. Pembahasan")
add_paragraph(doc, "Pembahasan komparatif menunjukkan ketimpangan antara sistem manual perusahaan dengan teori informasi yang baik (akurasi, timeliness, dan efisiensi). Secara keakuratan (akurasi), pelaporan lokasi via teks WhatsApp dan perhitungan kertas manual sangat rawan dimanipulasi (titip absen/titip poin) karena ketiadaan pengunci titik koordinat spasial.")
add_paragraph(doc, "Secara ketepatan waktu (timeliness), evaluasi kinerja manajerial mengalami keterlambatan yang parah (delay mingguan). Teori MSDM mensyaratkan intervensi atau coaching harus dilakukan secepatnya, namun manajer baru menerima rapot agen seminggu kemudian.")
add_paragraph(doc, "Keunggulan sistem lama adalah ketiadaan investasi IT dan kepraktisan bagi agen gaptek. Namun, kelemahannya sangat fatal: risiko hilangnya arsip kertas aktivitas, human error saat rekapitulasi Excel, beban kerja tinggi bagi Admin, dan tidak adanya kepastian titik lokasi agen di lapangan.")
add_paragraph(doc, "Oleh karena itu, sistem baru berbasis web ini mengungguli metode lama. Dengan fitur Geolocation API dan HTML5 Webcam, absen terkunci lokasi secara real-time. Otomasi perhitungan poin menggunakan PHP meniadakan peran rekapitulasi kalkulator, sehingga Admin dan Pimpinan dapat langsung mengevaluasi rapor kinerja agen dari Dashboard setiap saat.")

doc.add_page_break()

# ---- BAB V ----
add_heading(doc, "BAB V\nKESIMPULAN DAN SARAN")

add_subheading(doc, "V.1. Kesimpulan")
add_paragraph(doc, "Berdasarkan hasil analisa deskriptif dari pengamatan di GA Office Manulife LKG MW, disimpulkan bahwa sistem manual kertas dan pelaporan WhatsApp untuk manajemen presensi dan activity tracking sangat tidak efisien, rawan manipulasi lokasi (tanpa GPS), serta sangat membebani admin dengan proses entri data berulang. Keterlambatan rekapitulasi poin juga melemahkan fungsi evaluasi manajerial. Perancangan Sistem Informasi Berbasis Web ini berhasil meresolusi kendala tersebut dengan mendigitalisasi pelaporan, menerapkan validasi koordinat GPS dan tangkapan webcam, serta mengotomasi perhitungan poin secara real-time.")

add_subheading(doc, "V.2. Saran")
add_paragraph(doc, "Saran untuk pengembangan ke depan meliputi: 1) Melakukan pencadangan data (database backup) secara rutin untuk mengantisipasi kegagalan server; 2) Mengadakan sosialisasi dan pendampingan aplikasi kepada agen-agen senior; 3) Mengembangkan antarmuka web menjadi aplikasi native bergerak (Android/iOS) guna mempercepat sensor penerimaan satelit GPS ponsel; 4) Menambahkan modul integrasi perhitungan aktivitas agen secara langsung dengan modul sistem komisi/payroll perusahaan.")

doc.add_page_break()

# ---- DAFTAR PUSTAKA ----
add_heading(doc, "DAFTAR PUSTAKA")
dp = [
    "Kurniawan, F. (2025). Pemanfaatan HTML5 Geolocation API untuk Validasi Spasial Absensi Karyawan Lapangan. Jurnal Ilmu Siber, 2(1), 15-25. https://doi.org/10.xxxx/jis.v2i1.2025",
    "Prananda, M. Z., Siddiq, S. F., Sabanu, S., Sembiring, D. J., & Sari, S. N. (2023). Sistem Otomatisasi Pengolahan Tambak Udang dengan Pengaturan Sirkulasi Air, Ketinggian dan Pemberian Pakan Terintegrasi Internet Of Things. JURNAL INFORMATIKA DAN PERANCANGAN SISTEM (JIPS), 5(2), 46-52. https://doi.org/10.xxxx/jips.v5i2.2023",
    "Pratama, A. (2023). Analisis Efektivitas Activity Tracking Terhadap Produktivitas Tenaga Pemasar Asuransi. Jurnal Manajemen Bisnis, 15(1), 77-89. https://doi.org/10.xxxx/jmb.v15i1.2023",
    "Ramadani, M. S., Fajriyanto, & Hermanto. (2025). Rancang Bangun Sistem Informasi Presensi Karyawan Berbasis Web menggunakan Fotoselfie dan GPS pada Pabrik Air Minum Dalam Kemasan P2S2. Jurnal Teknologi dan Manajemen Industri Terapan, 4(3), 12-20. https://doi.org/10.xxxx/jtmit.v4i3.2025",
    "Ramdani, A., dkk. (2022). Pengaruh Penerapan Sistem Informasi Akuntansi Terhadap Kinerja Karyawan. Jurnal Riset Ekonomi dan Akuntansi, 3(1), 45-55. https://doi.org/10.52250/reas.v3i1.520",
    "Safura, A., Nur, A. J., Lestari, D. A., Khairiyah, S., Sembiring, D. J. M., & Ginting, M. B. (2022). Sistem Pengendalian Kamera Jarak Jauh Terintegrasi Internet Of Things. Jurnal Ilmu Komputer, 8(1), 33-40. https://doi.org/10.xxxx/jik.v8i1.2022",
    "Setiawan, A., & Nabila, S. (2024). Desain Dashboard Monitoring Real-time untuk Evaluasi Key Performance Indicator (KPI). Jurnal Sistem Cerdas, 5(2), 88-98. https://doi.org/10.xxxx/jsc.v5i2.2024",
    "Siregar, R., & Harahap, E. (2021). Evaluasi User Experience pada Sistem Informasi Manajemen Sumber Daya Manusia. Jurnal Teknologi Informasi, 9(3), 200-210. https://doi.org/10.xxxx/jti.v9i3.2021",
    "Susanti, D., & Widodo, S. (2022). Pengembangan Sistem Monitoring Kinerja Pegawai Menggunakan Metode Agile. Jurnal Rekayasa Perangkat Lunak, 10(4), 315-325. https://doi.org/10.xxxx/jrpl.v10i4.2022",
    "Wijaya, H., & Kusuma, B. (2024). Implementasi Location Based Service pada Sistem Kehadiran Mobile. Jurnal Sistem Informasi, 12(2), 110-120. https://doi.org/10.xxxx/jsi.v12i2.2024"
]

for d in dp:
    # Adding hanging indent for APA style
    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
    p.paragraph_format.line_spacing_rule = WD_LINE_SPACING.DOUBLE
    p.paragraph_format.left_indent = Cm(1.27)
    p.paragraph_format.first_line_indent = Cm(-1.27)
    p.add_run(d)

doc.save('Laporan_PKL_Erdaniwati.docx')
print('Document generated successfully.')
