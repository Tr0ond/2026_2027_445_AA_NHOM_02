document.addEventListener("DOMContentLoaded", function() {
    "use strict";

    // Hàm kiểm tra xem một phần tử có class đã được tạo ra hay chưa
    function hasClass(element, className) {
        return (' ' + element.className + ' ').indexOf(' ' + className + ' ') > -1;
    }

    // Hàm thêm class vào một phần tử
    function addClass(element, className) {
        if (!hasClass(element, className)) {
            element.className += ' ' + className;
        }
    }

    // Hàm xoá class khỏi một phần tử
    function removeClass(element, className) {
        if (hasClass(element, className)) {
            element.className = element.className.replace(new RegExp('(\\s|^)' + className + '(\\s|$)'), ' ').trim();
        }
    }

    // Hàm tạo Perfect Scrollbar
    function initializePerfectScrollbar(selector) {
        const container = document.querySelector(selector);
        if (container) {
            new PerfectScrollbar(container);
        }
    }

    // Tạo Perfect Scrollbar cho các phần tử cần thiết
    initializePerfectScrollbar('.header-message-list');
    initializePerfectScrollbar('.header-notifications-list');

    // Tạo sự kiện click cho nút mobile-search-icon
    const searchIcon = document.querySelector(".mobile-search-icon");
    if (searchIcon) {
        searchIcon.addEventListener("click", function () {
            addClass(document.querySelector(".search-bar"), "full-search-bar");
        });
    }

    // Tạo sự kiện click cho nút search-close
    const searchClose = document.querySelector(".search-close");
    if (searchClose) {
        searchClose.addEventListener("click", function () {
            removeClass(document.querySelector(".search-bar"), "full-search-bar");
        });
    }

    // Tạo sự kiện click cho nút mobile-toggle-menu
    const mobileToggleMenu = document.querySelector(".mobile-toggle-menu");
    if (mobileToggleMenu) {
        mobileToggleMenu.addEventListener("click", function () {
            addClass(document.querySelector(".wrapper"), "toggled");
        });
    }

    // Tạo sự kiện click cho nút toggle-icon
    const toggleIcon = document.querySelector(".toggle-icon");
    if (toggleIcon) {
        toggleIcon.addEventListener("click", function () {
            const wrapper = document.querySelector(".wrapper");
            if (hasClass(wrapper, "toggled")) {
                removeClass(wrapper, "toggled");
                // document.querySelector(".sidebar-wrapper").onmouseenter = null;
            } else {
                addClass(wrapper, "toggled");
                document.querySelector(".sidebar-wrapper").onmouseenter = function () {
                    addClass(wrapper, "sidebar-hovered");
                };
                document.querySelector(".sidebar-wrapper").onmouseleave = function () {
                    removeClass(wrapper, "sidebar-hovered");
                };
            }
        });
    }

    // Tạo sự kiện scroll cho window để hiển thị nút back-to-top
    window.addEventListener("scroll", function () {
        if (window.pageYOffset > 300) {
            document.querySelector('.back-to-top').style.display = "block";
        } else {
            document.querySelector('.back-to-top').style.display = "none";
        }
    });

    // Tạo sự kiện click cho nút back-to-top
    const backToTop = document.querySelector('.back-to-top');
    if (backToTop) {
        backToTop.addEventListener("click", function () {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    // Tạo sự kiện click cho nút chat-toggle-btn
    const chatToggleBtn = document.querySelector(".chat-toggle-btn");
    if (chatToggleBtn) {
        chatToggleBtn.addEventListener("click", function () {
            document.querySelector(".chat-wrapper").classList.toggle("chat-toggled");
        });
    }

    // Tạo sự kiện click cho nút chat-toggle-btn-mobile
    const chatToggleBtnMobile = document.querySelector(".chat-toggle-btn-mobile");
    if (chatToggleBtnMobile) {
        chatToggleBtnMobile.addEventListener("click", function () {
            document.querySelector(".chat-wrapper").classList.remove("chat-toggled");
        });
    }

    // Tạo sự kiện click cho nút email-toggle-btn
    const emailToggleBtn = document.querySelector(".email-toggle-btn");
    if (emailToggleBtn) {
        emailToggleBtn.addEventListener("click", function () {
            document.querySelector(".email-wrapper").classList.toggle("email-toggled");
        });
    }

    // Tạo sự kiện click cho nút email-toggle-btn-mobile
    const emailToggleBtnMobile = document.querySelector(".email-toggle-btn-mobile");
    if (emailToggleBtnMobile) {
        emailToggleBtnMobile.addEventListener("click", function () {
            document.querySelector(".email-wrapper").classList.remove("email-toggled");
        });
    }

    // Tạo sự kiện click cho nút compose-mail-btn
    const composeMailBtn = document.querySelector(".compose-mail-btn");
    if (composeMailBtn) {
        composeMailBtn.addEventListener("click", function () {
            document.querySelector(".compose-mail-popup").style.display = "block";
        });
    }

    // Tạo sự kiện click cho nút compose-mail-close
    const composeMailClose = document.querySelector(".compose-mail-close");
    if (composeMailClose) {
        composeMailClose.addEventListener("click", function () {
            document.querySelector(".compose-mail-popup").style.display = "none";
        });
    }

    // Tạo sự kiện click cho nút switcher-btn
    const switcherBtn = document.querySelector(".switcher-btn");
    if (switcherBtn) {
        switcherBtn.addEventListener("click", function () {
            document.querySelector(".switcher-wrapper").classList.toggle("switcher-toggled");
        });
    }

    // Tạo sự kiện click cho nút close-switcher
    const closeSwitcher = document.querySelector(".close-switcher");
    if (closeSwitcher) {
        closeSwitcher.addEventListener("click", function () {
            document.querySelector(".switcher-wrapper").classList.remove("switcher-toggled");
        });
    }

    // Xử lý các sự kiện click cho các mục chọn chế độ giao diện
    const themeButtons = document.querySelectorAll(".theme-button");
    themeButtons.forEach(function(button) {
        button.addEventListener("click", function () {
            const theme = this.dataset.theme;
            document.documentElement.setAttribute('class', theme);
        });
    });

    // Xử lý các sự kiện click cho các mục chọn màu header
    const headerColorButtons = document.querySelectorAll(".header-color-button");
    headerColorButtons.forEach(function(button) {
        button.addEventListener("click", function () {
            const colorClass = this.dataset.color;
            document.documentElement.className = document.documentElement.className.replace(/\bcolor-header-\S+/g, '');
            document.documentElement.classList.add("color-header", colorClass);
        });
    });

});
