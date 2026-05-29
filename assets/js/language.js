// assets/js/language.js
document.addEventListener("DOMContentLoaded", function() {
    initLanguage();
});

const translations = {
    pt: {
        nav_inicio: "Início",
        nav_sobre: "Sobre Nós",
        nav_acomodacoes: "Acomodações",
        nav_reservar: "Reservar",
        nav_contato: "Contato",
        nav_entrar: "Entrar",
        banner_h6: "Longe da vida monótona",
        banner_h2: "Relaxe sua Mente",
        banner_p: "O Epic Sana Luanda oferece uma experiência de luxo incomparável em Luanda, com vistas deslumbrantes e serviços de primeira classe.",
        banner_btn: "Reservar Agora",
        acc_title: "Acomodações do Hotel",
        acc_desc: "O Epic Sana Luanda oferece quartos luxuosos com vistas panorâmicas da baía de Luanda.",
        acc_book: "Reservar",
        fac_title: "Instalações do Epic Sana",
        fac_desc: "Desfrute de nossas instalações de luxo projetadas para o seu conforto e entretenimento.",
        footer_about_title: "Sobre o Hotel",
        footer_about_text: "O Epic Sana Luanda é um destino de luxo em Angola, oferecendo vistas deslumbrantes e serviços de alta qualidade para uma estadia inesquecível.",
        footer_links_title: "Links de Navegação",
        footer_newsletter_title: "Boletim Informativo",
        footer_newsletter_text: "Inscreva-se para receber as últimas novidades e ofertas especiais do Epic Sana Luanda."
    },
    en: {
        nav_inicio: "Home",
        nav_sobre: "About Us",
        nav_acomodacoes: "Accommodations",
        nav_reservar: "Book Now",
        nav_contato: "Contact",
        nav_entrar: "Login",
        banner_h6: "Away from monotonous life",
        banner_h2: "Relax Your Mind",
        banner_p: "Epic Sana Luanda offers an unparalleled luxury experience in Luanda, with stunning views and first-class services.",
        banner_btn: "Book Now",
        acc_title: "Hotel Accommodations",
        acc_desc: "Epic Sana Luanda offers luxurious rooms with panoramic views of Luanda Bay.",
        acc_book: "Book",
        fac_title: "Epic Sana Facilities",
        fac_desc: "Enjoy our luxury facilities designed for your comfort and entertainment.",
        footer_about_title: "About the Hotel",
        footer_about_text: "Epic Sana Luanda is a luxury destination in Angola, offering stunning views and high-quality services for an unforgettable stay.",
        footer_links_title: "Navigation Links",
        footer_newsletter_title: "Newsletter",
        footer_newsletter_text: "Subscribe to receive the latest news and special offers from Epic Sana Luanda."
    }
};

function initLanguage() {
    let currentLang = localStorage.getItem("site_lang") || "pt";
    setLanguage(currentLang);

    // Setup click events for language switch links
    document.querySelectorAll(".lang-switch").forEach(el => {
        el.addEventListener("click", function(e) {
            e.preventDefault();
            let selectedLang = this.getAttribute("data-lang");
            localStorage.setItem("site_lang", selectedLang);
            location.reload();
        });
    });
}

function setLanguage(lang) {
    const dict = translations[lang] || translations['pt'];
    
    // Update navbar language display
    const dropdownToggle = document.getElementById("langDropdown");
    if(dropdownToggle) {
        dropdownToggle.innerHTML = `<i class="fa fa-globe"></i> ${lang.toUpperCase()} <span class="caret"></span>`;
    }

    // Static translations using data-translate key
    document.querySelectorAll("[data-translate]").forEach(el => {
        let key = el.getAttribute("data-translate");
        if(dict[key]) {
            if(el.tagName === 'A' && el.classList.contains('button_hover') && key === 'banner_btn') {
                el.textContent = dict[key];
            } else {
                el.textContent = dict[key];
            }
        }
    });

    // Translate placeholder attributes
    document.querySelectorAll("[data-translate-placeholder]").forEach(el => {
        let key = el.getAttribute("data-translate-placeholder");
        if(dict[key]) {
            el.setAttribute("placeholder", dict[key]);
        }
    });
}
