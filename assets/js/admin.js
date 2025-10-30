class AdminDashboard {
  constructor() {
    this.sidebarCollapsed = localStorage.getItem("sidebarCollapsed") === "true";
    this.init();
  }

  init() {
    this.setupSidebar();
    this.setupNavGroups();
    this.setupMobileMenu();
    this.applyInitialState();
  }

  setupSidebar() {
    const sidebar = document.getElementById("sidebar");
    const sidebarToggle = document.getElementById("sidebarToggle");
    const menuToggle = document.getElementById("menuToggle");

    // Toggle sidebar collapse/expand
    if (sidebarToggle) {
      sidebarToggle.addEventListener("click", () => {
        this.toggleSidebar();
      });
    }

    // Mobile menu toggle
    if (menuToggle) {
      menuToggle.addEventListener("click", () => {
        sidebar.classList.toggle("mobile-open");
      });
    }

    // Close mobile menu when clicking outside
    document.addEventListener("click", (e) => {
      if (window.innerWidth <= 768 && sidebar && !sidebar.contains(e.target) && menuToggle && !menuToggle.contains(e.target)) {
        sidebar.classList.remove("mobile-open");
      }
    });

    // Apply initial collapsed state
    if (this.sidebarCollapsed && sidebar) {
      sidebar.classList.add("collapsed");
    }
  }

  setupNavGroups() {
    const groupHeaders = document.querySelectorAll(".nav-group-header");

    groupHeaders.forEach((header) => {
      header.addEventListener("click", (e) => {
        e.preventDefault();
        this.toggleNavGroup(header);
      });
    });

    // Auto-expand group if current page is in that group
    this.autoExpandActiveGroup();
  }

  toggleNavGroup(header) {
    const group = header.getAttribute("data-group");
    const items = header.nextElementSibling;
    const arrow = header.querySelector(".nav-group-arrow");

    // Toggle collapsed state
    const isCollapsed = items.classList.contains("collapsed");

    if (isCollapsed) {
      // Expand
      items.classList.remove("collapsed");
      header.classList.add("active");
      arrow.classList.replace("fa-chevron-down", "fa-chevron-up");
    } else {
      // Collapse
      items.classList.add("collapsed");
      header.classList.remove("active");
      arrow.classList.replace("fa-chevron-up", "fa-chevron-down");
    }

    // Save state to cookie
    this.setCookie(`${group}Collapsed`, !isCollapsed ? "false" : "true", 30);
  }

  autoExpandActiveGroup() {
    const currentPage = window.location.pathname.split("/").pop() || "index.php";
    const activeLink = document.querySelector(".nav-link.active");

    if (activeLink) {
      const navGroup = activeLink.closest(".nav-group");
      if (navGroup) {
        const header = navGroup.querySelector(".nav-group-header");
        const items = navGroup.querySelector(".nav-group-items");
        const arrow = header.querySelector(".nav-group-arrow");

        // Expand the group
        items.classList.remove("collapsed");
        header.classList.add("active");
        arrow.classList.replace("fa-chevron-down", "fa-chevron-up");

        // Update cookie
        const group = header.getAttribute("data-group");
        this.setCookie(`${group}Collapsed`, "false", 30);
      }
    }
  }

  toggleSidebar() {
    const sidebar = document.getElementById("sidebar");
    if (sidebar) {
      sidebar.classList.toggle("collapsed");
      this.sidebarCollapsed = sidebar.classList.contains("collapsed");

      // Save state to localStorage
      localStorage.setItem("sidebarCollapsed", this.sidebarCollapsed);
    }
  }

  setupMobileMenu() {
    // Handle window resize
    window.addEventListener("resize", () => {
      const sidebar = document.getElementById("sidebar");
      if (window.innerWidth > 768 && sidebar) {
        sidebar.classList.remove("mobile-open");
      }
    });
  }

  applyInitialState() {
    // Apply saved sidebar state
    const sidebar = document.getElementById("sidebar");
    if (this.sidebarCollapsed && sidebar) {
      sidebar.classList.add("collapsed");
    }
  }

  // Helper function to set cookie
  setCookie(name, value, days) {
    const date = new Date();
    date.setTime(date.getTime() + days * 24 * 60 * 60 * 1000);
    const expires = "expires=" + date.toUTCString();
    document.cookie = name + "=" + value + ";" + expires + ";path=/";
  }

  // Helper function to get cookie
  getCookie(name) {
    const nameEQ = name + "=";
    const ca = document.cookie.split(";");
    for (let i = 0; i < ca.length; i++) {
      let c = ca[i];
      while (c.charAt(0) === " ") c = c.substring(1, c.length);
      if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
    }
    return null;
  }
}

// Initialize dashboard when DOM is loaded
document.addEventListener("DOMContentLoaded", () => {
  new AdminDashboard();
});
