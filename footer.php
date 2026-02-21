  </div><!-- .main-content -->
  </div><!-- .main-wrap -->
  </div><!-- .main-container -->

  <script>
      // Reset sidebar visibility on resize (let CSS media queries handle display)
      const updateSidebarVisibility = () => {
          const sidebar = document.querySelector('.sidebar');
          const overlay = document.getElementById('sidebarOverlay');
          const width = window.innerWidth;

          // On mobile screens (< 768px), close sidebar and overlay on resize
          if (width < 768) {
              if (sidebar) sidebar.classList.remove('open');
              if (overlay) overlay.classList.remove('active');
          }
      };

      function setTheme(theme) {
          document.documentElement.setAttribute('data-theme', theme);
          localStorage.setItem('theme', theme);

          const themeToggle = document.getElementById('themeToggle');
          if (themeToggle) {
              themeToggle.innerHTML = theme === 'dark' ? '<i class="fa-solid fa-sun"></i>' : '<i class="fa-solid fa-moon"></i>';
              themeToggle.title = theme === 'dark' ? 'Basculer au mode clair' : 'Basculer au mode sombre';
          }
      }

      // Initialize all on page load
      document.addEventListener('DOMContentLoaded', function() {
          // Theme initialization
          const savedTheme = localStorage.getItem('theme') || 'dark';
          setTheme(savedTheme);

          const themeToggle = document.getElementById('themeToggle');
          if (themeToggle) {
              themeToggle.addEventListener('click', function() {
                  const currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
                  const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                  setTheme(newTheme);
              });
          }

          // Flash messages auto-dismiss
          const alerts = document.querySelectorAll('.alert');
          alerts.forEach(alert => {
              setTimeout(() => {
                  alert.style.opacity = '0';
                  setTimeout(() => alert.remove(), 300);
              }, 4000);
          });

          // Sidebar and hamburger menu setup
          const sidebar = document.querySelector('.sidebar');
          const sidebarToggle = document.getElementById('sidebarToggle');
          const sidebarOverlay = document.getElementById('sidebarOverlay');

          const openSidebar = () => {
              if (sidebar) sidebar.classList.add('open');
              if (sidebarOverlay) sidebarOverlay.classList.add('active');
          };

          const closeSidebar = () => {
              if (sidebar) sidebar.classList.remove('open');
              if (sidebarOverlay) sidebarOverlay.classList.remove('active');
          };

          updateSidebarVisibility();

          // Toggle sidebar on hamburger click
          if (sidebarToggle) {
              sidebarToggle.addEventListener('click', function(e) {
                  e.preventDefault();
                  if (sidebar && sidebar.classList.contains('open')) {
                      closeSidebar();
                  } else {
                      openSidebar();
                  }
              });
          }

          // Close sidebar when clicking overlay
          if (sidebarOverlay) {
              sidebarOverlay.addEventListener('click', closeSidebar);
          }

          // Close sidebar when clicking a nav link (all mobile)
          const navLinks = document.querySelectorAll('.nav-link');
          navLinks.forEach(link => {
              link.addEventListener('click', function() {
                  if (window.innerWidth < 768) {
                      closeSidebar();
                  }
              });
          });

          // Close sidebar when clicking outside (all mobile)
          document.addEventListener('click', function(e) {
              const width = window.innerWidth;
              if (width < 768 &&
                  sidebar && sidebar.classList.contains('open') &&
                  !sidebar.contains(e.target) &&
                  sidebarToggle && !sidebarToggle.contains(e.target) &&
                  sidebarOverlay && !sidebarOverlay.contains(e.target)) {
                  closeSidebar();
              }
          });

          // Form validation
          const forms = document.querySelectorAll('form');
          forms.forEach(form => {
              form.addEventListener('submit', function(e) {
                  const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
                  let isValid = true;

                  inputs.forEach(input => {
                      if (!input.value.trim()) {
                          isValid = false;
                          input.classList.add('is-invalid');
                      } else {
                          input.classList.remove('is-invalid');
                      }
                  });

                  if (!isValid) {
                      e.preventDefault();
                  }
              });
          });
      });

      // Handle window resize
      let resizeTimer;
      window.addEventListener('resize', function() {
          clearTimeout(resizeTimer);
          resizeTimer = setTimeout(function() {
              updateSidebarVisibility();
          }, 250);
      });
  </script>

  </body>

  </html>