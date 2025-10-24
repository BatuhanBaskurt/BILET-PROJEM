# 🚌 Otobüs Bilet Otomasyon Projesi (BILET-PROJEM)

Bu proje, PHP 8.1 ve SQLite kullanılarak geliştirilmiş, Docker ile paketlenmiş basit bir otobüs bileti satış otomasyon sistemidir. Proje, kullanıcıların seferleri görüntülemesine, bilet almasına ve yönetmesine olanak tanırken; şirket adminleri ve site adminleri için de yönetim panelleri sunar.

---

## 🚀 Özellikler

- **Kullanıcı Paneli:**
  - Sefer arama ve listeleme
  - Koltuk seçerek bilet satın alma
  - Kupon kullanarak indirimli bilet alma
  - Satın alınan biletleri görüntüleme ve PDF olarak indirme
  - Bilet iade etme
  - Profil bilgilerini ve geçmiş biletleri görüntüleme

- **Şirket Admin Paneli:**
  - Kendi şirketine ait seferleri oluşturma, düzenleme ve silme
  - Şirket logosunu ve bilgilerini güncelleme
  - Kendi şirketine özel indirim kuponları oluşturma

- **Ana Admin Paneli:**
  - Tüm otobüs şirketlerini yönetme (oluşturma, düzenleme, silme)
  - Tüm kullanıcıları ve rollerini yönetme

---

## 🛠️ Kullanılan Teknolojiler

- **Backend:** PHP 8.1
- **Veritabanı:** SQLite 3
- **Web Sunucusu:** Apache
- **PDF Kütüphanesi:** TCPDF
- **Konteynerleştirme:** Docker & Docker Compose

---

## ⚙️ Kurulum ve Çalıştırma

Bu proje, Docker ile paketlendiği için kurulum adımları işletim sisteminize göre farklılık gösterir.

### 🐧 Linux (veya macOS) Kullanıcıları İçin

Linux ve macOS, Linux konteynerlerini doğal olarak çalıştırabilir. Sisteminizde `git` ve `docker` kuruluysa, proje 3 komutla çalışmaya hazırdır:

1.  **Projeyi klonlayın:**
    ```bash
    git clone [https://github.com/BatuhanBaskurt/BILET-PROJEM.git](https://github.com/BatuhanBaskurt/BILET-PROJEM.git)
    ```

2.  **Proje klasörüne gidin:**
    ```bash
    cd BILET-PROJEM
    ```

3.  **Docker konteynerini çalıştırın:**
    ```bash
    docker-compose up --build
    ```

4.  **Siteye erişin:**
    Tarayıcınızı açın ve `http://localhost:8000` adresine gidin.

---

### 🪟 Windows Kullanıcıları İçin

Windows, Linux konteynerlerini doğrudan çalıştıramaz. **WSL 2 (Windows Subsystem for Linux)** adında bir "adaptör" katmanına ihtiyaç duyar.

#### Adım 1: Gerekli Programların Kurulumu (Sadece 1 Kez Yapılır)

Eğer bu programlar sisteminizde kurulu değilse, projeyi çalıştırmadan önce **mutlaka** kurmanız gerekir:

1.  **Git:**
    * [https://git-scm.com/downloads](https://git-scm.com/downloads) adresinden indirin ve kurun.

2.  **WSL 2 (Linux Alt Sistemi):**
    * Başlat menüsünden "PowerShell"i bulun, sağ tıklayın ve **"Yönetici olarak çalıştır"** seçin.
    * Açılan terminale şu komutu yazın ve `Enter`'a basın:
      ```bash
      wsl --install
      ```
    * İşlem bittiğinde bilgisayarınızı **mutlaka yeniden başlatın**.

3.  **Docker Desktop:**
    * [https://www.docker.com/products/docker-desktop/](https://www.docker.com/products/docker-desktop/) adresinden indirin ve kurun.
    * Kurulum sırasında **"Use WSL 2 based engine"** (WSL 2 tabanlı motoru kullan) seçeneğini işaretlediğinizden emin olun.

#### Adım 2: Docker Ayarlarının Kontrolü

1.  Bilgisayarınız yeniden başladıktan sonra **Docker Desktop** programını çalıştırın.
2.  Program açılınca, sağ üstteki **Ayarlar (Settings ⚙️)** ikonuna tıklayın.
3.  **General** sekmesinde, **"Use the WSL 2 based engine"** seçeneğinin **işaretli** olduğundan emin olun. (Eğer değilse, işaretleyip "Apply & Restart" butonuna basın).

#### Adım 3: Projenin Çalıştırılması

Yukarıdaki tüm adımlar tamamsa, artık projeyi çalıştırabilirsiniz:

1.  **Projeyi klonlayın:**
    * (Eğer "Git"i yeni kurduysanız, Terminali kapatıp açın)
    ```bash
    git clone [https://github.com/BatuhanBaskurt/BILET-PROJEM.git](https://github.com/BatuhanBaskurt/BILET-PROJEM.git)
    ```

2.  **Proje klasörüne gidin:**
    ```bash
    cd BILET-PROJEM
    ```

3.  **Docker konteynerini çalıştırın:**
    ```bash
    docker-compose up --build
    ```

4.  **Siteye erişin:**
    Tarayıcınızı açın ve `http://localhost:8000` adresine gidin.

---

## 🔑 Demo Giriş Bilgileri

Projeyi test etmek için aşağıdaki hazır kullanıcıları kullanabilirsiniz. Veritabanı (`database.db`) proje ile birlikte geldiği için bu kullanıcılar varsayılan olarak mevcuttur.

- **Ana Admin:** `(admin.php)`
  - **E-posta:** `admin@admin.com`
  - **Şifre:** `admin`

- **Şirket Admini:**
  - **E-posta:** `comp@comp.com`
  - **Şifre:** `comp`

- **Normal Kullanıcı:**
  - Yeni bir kullanıcı olarak kayıt olabilirsiniz. Varsayılan bakiye ₺800 olarak atanır.

---

**Not:** Bu proje bir ödev olarak geliştirilmiştir. Güvenlik önlemleri (CSRF, XSS koruması, güvenli dosya yükleme, PDO) alınmış olsa da, gerçek bir canlı ortamda kullanılması tavsiye edilmez.
