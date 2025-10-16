# 🚌 Otobüs Bilet Otomasyon Projesi (BILET-PROJEM)

Bu proje, PHP ve SQLite kullanılarak geliştirilmiş, Docker ile paketlenmiş basit bir otobüs bileti satış otomasyon sistemidir. Proje, kullanıcıların seferleri görüntülemesine, bilet almasına ve yönetmesine olanak tanırken; şirket adminleri ve site adminleri için de yönetim panelleri sunar.

---

## 🚀 Özellikler

- **Kullanıcı Paneli:**
  - Sefer arama ve listeleme
  - Koltuk seçerek bilet satın alma
  - Kupon kullanarak indirimli bilet alma
  - Satın alınan biletleri görüntüleme ve PDF olarak indirme
  - Bilet iade etme
  - Profil bilgilerini ve geçmiş biletleri görüntüleme

- **Şirket Admin Paneli (`comp_admin`):**
  - Kendi şirketine ait seferleri oluşturma, düzenleme ve silme
  - Şirket logosunu ve bilgilerini güncelleme
  - Kendi şirketine özel indirim kuponları oluşturma

- **Ana Admin Paneli (`admin`):**
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

Bu proje, Docker sayesinde herhangi bir ek kurulum gerektirmeden tek komutla çalıştırılabilir.

### Gereksinimler
- [Git](https://git-scm.com/)
- [Docker](https://www.docker.com/products/docker-desktop/)

### Adımlar

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
    Bu komut, gerekli tüm PHP eklentilerini ve bağımlılıkları içeren sanal bir sunucu oluşturup projeyi başlatacaktır.

4.  **Siteye erişin:**
    Tarayıcınızı açın ve `http://localhost:8000` adresine gidin.

---

## 🔑 Demo Giriş Bilgileri

Projeyi test etmek için aşağıdaki hazır kullanıcıları kullanabilirsiniz. Veritabanı (`database.db`) proje ile birlikte geldiği için bu kullanıcılar varsayılan olarak mevcuttur.

- **Ana Admin:**
  - **E-posta:** `admin@admin.com`
  - **Şifre:** `admin`

- **Şirket Admini:**
  - **E-posta:** `comp@comp.com`
  - **Şifre:** `comp` 

- **Normal Kullanıcı:**
  - Yeni bir kullanıcı olarak kayıt olabilirsiniz. Varsayılan bakiye ₺800 olarak atanır.

---

**Not:** Bu proje bir ödev olarak geliştirilmiştir. Güvenlik önlemleri (CSRF, XSS koruması, güvenli dosya yükleme, PDO) alınmış olsa da, gerçek bir canlı ortamda kullanılması tavsiye edilmez.
