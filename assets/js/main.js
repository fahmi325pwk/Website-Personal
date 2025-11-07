// ===== BACK TO TOP FUNCTIONALITY =====
document.addEventListener("DOMContentLoaded", function () {
  const backToTopBtn = document.getElementById("back-to-top");

  // Show/hide button based on scroll position
  window.addEventListener("scroll", function () {
    if (window.pageYOffset > 300) {
      backToTopBtn.classList.add("show");
    } else {
      backToTopBtn.classList.remove("show");
    }
  });

  // Smooth scroll to top
  backToTopBtn.addEventListener("click", function () {
    window.scrollTo({
      top: 0,
      behavior: "smooth",
    });
  });

  // ===== NEWSLETTER FORM HANDLING =====
  const newsletterForm = document.querySelector(".subscribe-form");
  if (newsletterForm) {
    newsletterForm.addEventListener("submit", function (e) {
      e.preventDefault();
      const email = this.querySelector('input[type="email"]').value;

      // Simple email validation
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(email)) {
        alert("Silakan masukkan alamat email yang valid.");
        return;
      }

      // Simulate form submission
      alert("Terima kasih! Anda telah berhasil berlangganan newsletter kami.");
      this.reset();
    });
  }

  // ===== ENHANCED PRODUCT CARD ANIMATIONS =====
  const productCards = document.querySelectorAll(".product-card");
  productCards.forEach((card, index) => {
    card.style.animationDelay = `${index * 0.1}s`;
  });

  // ===== TESTIMONIAL CARDS ANIMATION =====
  const testimonialCards = document.querySelectorAll(".testimonial-card");
  testimonialCards.forEach((card, index) => {
    card.style.animationDelay = `${index * 0.1}s`;
  });

  // ===== LOADING ANIMATION =====
  const loadingOverlay = document.querySelector(".loading-overlay");
  if (loadingOverlay) {
    setTimeout(() => {
      loadingOverlay.style.display = "none";
    }, 2000);
  }

  // ===== SMOOTH SCROLL FOR ANCHOR LINKS =====
  const anchorLinks = document.querySelectorAll('a[href^="#"]');
  anchorLinks.forEach((link) => {
    link.addEventListener("click", function (e) {
      const targetId = this.getAttribute("href");
      const targetElement = document.querySelector(targetId);

      if (targetElement) {
        e.preventDefault();
        targetElement.scrollIntoView({
          behavior: "smooth",
          block: "start",
        });
      }
    });
  });

  // ===== ENHANCE HERO SECTION =====
  const heroSection = document.querySelector(".hero");
  if (heroSection) {
    // Add parallax effect
    window.addEventListener("scroll", function () {
      const scrolled = window.pageYOffset;
      const rate = scrolled * -0.5;
      heroSection.style.backgroundPosition = `center ${rate}px`;
    });
  }

  // ===== PRODUCT CARD HOVER EFFECTS =====
  productCards.forEach((card) => {
    card.addEventListener("mouseenter", function () {
      this.style.transform = "translateY(-8px) scale(1.02)";
    });

    card.addEventListener("mouseleave", function () {
      this.style.transform = "translateY(0) scale(1)";
    });
  });

  // ===== CATEGORY CARD HOVER EFFECTS =====
  const categoryCards = document.querySelectorAll(".category-card");
  categoryCards.forEach((card) => {
    card.addEventListener("mouseenter", function () {
      this.style.transform = "translateY(-10px) scale(1.02)";
    });

    card.addEventListener("mouseleave", function () {
      this.style.transform = "translateY(0) scale(1)";
    });
  });

  // ===== FEATURE ITEM HOVER EFFECTS =====
  const featureItems = document.querySelectorAll(".feature-item");
  featureItems.forEach((item) => {
    item.addEventListener("mouseenter", function () {
      this.style.transform = "translateY(-5px)";
    });

    item.addEventListener("mouseleave", function () {
      this.style.transform = "translateY(0)";
    });
  });

  // ===== TESTIMONIAL CARD HOVER EFFECTS =====
  testimonialCards.forEach((card) => {
    card.addEventListener("mouseenter", function () {
      this.style.transform = "translateY(-8px)";
    });

    card.addEventListener("mouseleave", function () {
      this.style.transform = "translateY(0)";
    });
  });

  // ===== RESPONSIVE NAVIGATION =====
  const navbar = document.querySelector(".navbar");
  let lastScrollTop = 0;

  window.addEventListener("scroll", function () {
    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;

    if (scrollTop > lastScrollTop && scrollTop > 100) {
      // Scrolling down
      navbar.style.transform = "translateY(-100%)";
    } else {
      // Scrolling up
      navbar.style.transform = "translateY(0)";
    }

    lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
  });

  // ===== INTERSECTION OBSERVER FOR ANIMATIONS =====
  const observerOptions = {
    threshold: 0.1,
    rootMargin: "0px 0px -50px 0px",
  };

  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.style.opacity = "1";
        entry.target.style.transform = "translateY(0)";
      }
    });
  }, observerOptions);

  // Observe elements for animation
  const animateElements = document.querySelectorAll(
    ".product-card, .category-card, .feature-item, .testimonial-card"
  );
  animateElements.forEach((el) => {
    el.style.opacity = "0";
    el.style.transform = "translateY(30px)";
    el.style.transition = "opacity 0.6s ease, transform 0.6s ease";
    observer.observe(el);
  });

  // ===== ENHANCED SEARCH FUNCTIONALITY =====
  const searchInput = document.querySelector(".form-control");
  if (searchInput) {
    let searchTimeout;
    searchInput.addEventListener("input", function () {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(() => {
        const query = this.value.toLowerCase();
        // Add search logic here if needed
        console.log("Searching for:", query);
      }, 300);
    });
  }

  // ===== DYNAMIC YEAR IN FOOTER =====
  const copyrightElement = document.querySelector(".copyright");
  if (copyrightElement) {
    const currentYear = new Date().getFullYear();
    copyrightElement.innerHTML = copyrightElement.innerHTML.replace(
      "2024",
      currentYear
    );
  }

  // ===== ACCESSIBILITY IMPROVEMENTS =====
  // Add focus states for keyboard navigation
  const focusableElements = document.querySelectorAll(
    "a, button, input, textarea, select"
  );
  focusableElements.forEach((el) => {
    el.addEventListener("focus", function () {
      this.style.outline = "2px solid var(--primary-color)";
      this.style.outlineOffset = "2px";
    });

    el.addEventListener("blur", function () {
      this.style.outline = "";
      this.style.outlineOffset = "";
    });
  });

  // ===== PERFORMANCE OPTIMIZATION =====
  // Lazy load images
  const images = document.querySelectorAll("img[data-src]");
  if ("IntersectionObserver" in window) {
    const imageObserver = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          const img = entry.target;
          img.src = img.dataset.src;
          img.classList.remove("lazy");
          imageObserver.unobserve(img);
        }
      });
    });

    images.forEach((img) => imageObserver.observe(img));
  } else {
    // Fallback for browsers without IntersectionObserver
    images.forEach((img) => {
      img.src = img.dataset.src;
    });
  }

  // ===== ERROR HANDLING =====
  window.addEventListener("error", function (e) {
    console.error("JavaScript error:", e.error);
    // Could send error to logging service
  });

  // ===== SUCCESS MESSAGE FOR FORMS =====
  function showSuccessMessage(message) {
    const successDiv = document.createElement("div");
    successDiv.className = "alert alert-success";
    successDiv.innerHTML = `<i class="bi bi-check-circle-fill"></i> ${message}`;
    successDiv.style.position = "fixed";
    successDiv.style.top = "20px";
    successDiv.style.right = "20px";
    successDiv.style.zIndex = "10000";
    successDiv.style.maxWidth = "300px";

    document.body.appendChild(successDiv);

    setTimeout(() => {
      successDiv.remove();
    }, 5000);
  }

  // ===== CART FUNCTIONALITY ENHANCEMENT =====
  const cartButtons = document.querySelectorAll(".btn-cart");
  cartButtons.forEach((btn) => {
    btn.addEventListener("click", function (e) {
      e.preventDefault();
      // Add to cart animation
      this.innerHTML = '<i class="bi bi-check-lg"></i>';
      this.style.background = "#27ae60";
      this.disabled = true;

      setTimeout(() => {
        this.innerHTML = '<i class="bi bi-cart3"></i>';
        this.style.background = "";
        this.disabled = false;
      }, 2000);
    });
  });

  console.log("BackKomputer website enhancements loaded successfully!");
});
