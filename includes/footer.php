<style>
    /* Keep the two footer logos inside the viewport on small screens */
    @media (max-width: 575.98px) {
        footer .row > div:first-child > .d-flex {
            flex-wrap: wrap;
            row-gap: 0.35rem;
        }
        footer .row > div:first-child > .d-flex > img {
            height: clamp(24px, 9vw, 36px) !important;
        }
        footer .row > div:first-child > .d-flex > div[style] {
            height: 24px !important;
        }
    }
</style>
<footer class="py-5 mt-auto text-white" style="background-color: #004d40; border-top: 3px solid var(--brand-teal, #00796B);">
    <div class="container">
        <div class="row g-4">
            
            <!-- Left Branding & Capstone Project Credits -->
            <div class="col-md-6 text-center text-md-start d-flex flex-column justify-content-center">
                <div class="d-flex align-items-center justify-content-center justify-content-md-start mb-3">
                    <img src="assets/img/logoNuqtah_White.png" alt="Logo Nuqtah" height="45" class="me-3" style="filter: drop-shadow(0 2px 4px rgba(0,0,0,0.15));">
                    <div class="border-start border-light opacity-50 mx-1" style="height: 30px;"></div>
                    <img src="assets/img/ITQSHHBLogo.png" alt="ITQSHHB Logo" height="50" class="ms-3" style="filter: drop-shadow(0 2px 4px rgba(0,0,0,0.15));">
                </div>                
                
                <p class="small mb-1 fw-bold">© 2026 Institut Tahfiz Al-Quran Sultan Haji Hassanal Bolkiah</p>
                <p class="small text-white-50 mb-0">Developed by Muhammad Amir Sabrin B20230012</p>
                <p class="small text-white-50">SCI Capstone Project - NUQTAH</p>
            </div>

            <!-- Right Location Details and Dynamic Interactive Map Container -->
            <div class="col-md-6">
                <div class="d-flex flex-column align-items-center align-items-md-end">
                    <h6 class="text-uppercase small fw-bold mb-2" style="letter-spacing: 1px; color: #a5d6a7;">Location</h6>
                    
                    <p class="small mb-3 text-center text-md-end text-white-50" style="line-height: 1.5;">
                        <i class="bi bi-geo-alt-fill me-2 text-warning"></i>KM 2, Simpang 175, Jalan Tutong,<br>
                        Bandar Seri Begawan BA2112, Brunei
                    </p>
                    
                    <div class="map-container shadow overflow-hidden rounded-3" style="width: 100%; max-width: 350px; border: 1px solid rgba(255, 255, 255, 0.15);">
                        <iframe 
                            src="https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d3193.736606616928!2d114.9275815!3d4.8812555!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x32228ab8992e34bf%3A0x5cce884b90949978!2sSultan%20Haji%20Hassanal%20Bolkiah%20Institute%20of%20Tahfiz!5e1!3m2!1sen!2sbn!4v1771381799498!5m2!1sen!2sbn" 
                            width="100%" 
                            height="120" 
                            style="border:0; filter: grayscale(10%) contrast(95%); display: block;" 
                            allowfullscreen="" 
                            loading="lazy">
                        </iframe>
                    </div>
                </div>
            </div>

        </div>
    </div>
</footer>