 <!-- ===== MODERN FOOTER ===== -->
      <footer id="tentang-kami" class="footer">
      <div class="footer-wave">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320" preserveAspectRatio="none">
          <path fill="#ffffff" fill-opacity="0.1" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,144C960,149,1056,139,1152,122.7C1248,107,1344,85,1392,74.7L1440,64L1440,0L1392,0C1344,0,1248,0,1152,0C1056,0,960,0,864,0C768,0,672,0,576,0C480,0,384,0,288,0C192,0,96,0,48,0L0,0Z"></path>
        </svg>
      </div>

      <div class="container">
        <div class="footer-content">
          <!-- Column 1: About -->
          <div class="footer-column">
            <h3 class="footer-logo">
              <i class="bi bi-pc-display-horizontal"></i>
              Nano Komputer
            </h3>
            <p class="footer-description">
              Nano Komputer menyediakan berbagai sparepart dan komponen komputer berkualitas dari brand
              ternama, dengan layanan profesional dan terpercaya untuk memenuhi kebutuhan PC Anda.
            </p>
            <div class="footer-social">
              <a href="https://www.tiktok.com/@nanokomputerofficial" class="social-link" aria-label="Facebook">
                <i class="bi bi-tiktok"></i>
              </a>
              <a href="https://www.instagram.com/nanokomputer/" class="social-link" aria-label="Instagram">
                <i class="bi bi-instagram"></i>
              </a>
              <a href="https://wa.me/081808415055" class="social-link" aria-label="YouTube">
                <i class="bi bi-whatsapp"></i>
              </a>
                <a href="https://www.facebook.com/nanokomputerindonesia/about/" class="social-link" aria-label="YouTube">
                <i class="bi bi-facebook"></i>
              </a>
              </a>
                <a href="https://www.youtube.com/@nanokomputerofficial/featured/" class="social-link" aria-label="YouTube">
                <i class="bi bi-youtube"></i>
              </a>
            </div>
          </div>

          <!-- Column 2: Quick Links -->
          <div class="footer-column">
            <h4 class="footer-title">Menu Cepat</h4>
            <ul class="footer-links">
              <li><a href="index.php"><i class="bi bi-chevron-right"></i> Beranda</a></li>
              <li><a href="products.php"><i class="bi bi-chevron-right"></i> Produk</a></li>
            </ul>
          </div>

          <!-- Column 3: Categories -->
          <div class="footer-column">
            <h4 class="footer-title">Kategori Produk</h4>
            <ul class="footer-links">
              <li><a href="products.php?category=storage"><i class="bi bi-chevron-right"></i> Penyimpanan</a></li>
              <li><a href="products.php?category=memory"><i class="bi bi-chevron-right"></i> Memori</a></li>
              <li><a href="products.php?category=gpu"><i class="bi bi-chevron-right"></i> VGA & GPU</a></li>
              <li><a href="products.php?category=motherboard"><i class="bi bi-chevron-right"></i> Motherboard</a></li>
            </ul>
          </div>

          <!-- Column 4: Contact -->
          <div class="footer-column">
            <h4 class="footer-title">Hubungi Kami</h4>
            <ul class="footer-contact">
              <li>
                <i class="bi bi-geo-alt-fill"></i>
                <span>Mangga Dua Mall Lantai 2 No.47A-B,<br>Jakarta,Indonesia,10730</span>
              </li>
              <li>
                <i class="bi bi-telephone-fill"></i>
                <span>+62 818-0841-5055</span>
              </li>
              <li>
                <i class="bi bi-clock-fill"></i>
                <span>Senin - Sabtu: 11:00 - 18:00</span>
              </li>
              <li>
              <i class="bi bi-envelope-at"></i>
                 <span>komputernano68@gmail.com</span>
              </li>
            </ul>
          </div>
        </div>

        <!-- Footer Bottom -->
        <div class="footer-bottom">
          <div class="footer-bottom-content">
            <p class="copyright">
              © 2025 <strong>Nano Komputer</strong>. Semua Hak Dilindungi.
            </p>
            <div class="footer-bottom-links">
              <a href="#">Kebijakan Privasi</a>
              <span class="separator">•</span>
              <a href="#">Syarat & Ketentuan</a>
              <span class="separator">•</span>
              <a href="#">FAQ</a>
            </div>
          </div>
        </div>
      </div>

      <!-- Back to Top Button -->
      <button class="back-to-top" id="backToTop" aria-label="Back to top">
        <i class="bi bi-arrow-up"></i>
      </button>
    </footer>
  </div>

  <!-- Back to Top Script -->
  <script>
    // Back to top button functionality
    const backToTopBtn = document.getElementById('backToTop');

    window.addEventListener('scroll', () => {
      if (window.pageYOffset > 300) {
        backToTopBtn.classList.add('show');
      } else {
        backToTopBtn.classList.remove('show');
      }
    });

    backToTopBtn.addEventListener('click', () => {
      window.scrollTo({
        top: 0,
        behavior: 'smooth'
      });
    });
  </script>
</body>