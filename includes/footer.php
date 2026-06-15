<?php

// Pied de page partagé contenant les horaires, les contacts et les liens vers les réseaux sociaux

$language = $_COOKIE['lang'] ?? 'fr';
if ($language !== 'ar' && $language !== 'fr') {
    $language = 'fr';
}

// Définir les traductions du pied de page en français et en arabe
$translation = [
    'fr' => [
        'brand' => 'Dr. Dghar Mohamed',
        'desc' => 'Une médecine de proximité et de qualité au service de votre famille.',
        'hours_title' => 'Horaires',
        'hours' => 'Lun - Ven: 09:00 - 18:00',
        'saturday' => 'Samedi: 09:00 - 13:00',
        'emergency' => 'Urgence: 06 15 50 13 39',
        'contact' => 'Contact',
        'address' => 'Tanger, Maroc',
        'copyright' => '&copy; ' . date('Y') . ' Dr. Dghar Mohamed. Tous droits réservés.',
        'legal' => 'Mentions légales',
        'privacy' => 'Confidentialité'
    ],
    'ar' => [
        'brand' => 'د. ادغار محمد',
        'desc' => 'طب منزلي ونوعي في خدمة عائلتكم.',
        'hours_title' => 'ساعات العمل',
        'hours' => 'الاثنين - الجمعة: 09:00 - 18:00',
        'saturday' => 'السبت: 09:00 - 13:00',
        'emergency' => 'الطوارئ: 06 15 50 13 39',
        'contact' => 'اتصال',
        'address' => 'طنجة، المغرب',
        'copyright' => '&copy; ' . date('Y') . ' د. ادغار محمد. جميع الحقوق محفوظة.',
        'legal' => 'المعلومات القانونية',
        'privacy' => 'الخصوصية'
    ]
];
?>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">

                <!-- Brand + social -->
                <div>
                    <div class="footer-logo">
                        <img src="assets/images/DoctorLogo.png" alt="Logo" width="44" height="44">
                        <span><?php echo $translation[$language]['brand']; ?></span>
                    </div>
                    <p><?php echo $translation[$language]['desc']; ?></p>
                    <div class="social-links">
                        <a href="#" class="social-link" aria-label="Facebook"><i class="fa-brands fa-facebook social-link-icon"></i></a>
                        <a href="#" class="social-link" aria-label="Instagram"><i class="fa-brands fa-instagram social-link-icon"></i></a>
                        <a href="#" class="social-link" aria-label="LinkedIn"><i class="fa-brands fa-linkedin social-link-icon"></i></a>
                    </div>
                </div>

                <!-- Hours -->
                <div class="footer-col">
                    <h4><?php echo $translation[$language]['hours_title']; ?></h4>
                    <ul>
                        <li><?php echo $translation[$language]['hours']; ?></li>
                        <li><?php echo $translation[$language]['saturday']; ?></li>
                        <li class="footer-urgent"><?php echo $translation[$language]['emergency']; ?></li>
                    </ul>
                </div>

                <!-- Contact -->
                <div class="footer-col">
                    <h4><?php echo $translation[$language]['contact']; ?></h4>
                    <ul>
                        <li><?php echo $translation[$language]['address']; ?></li>
                        <li><a href="tel:+212615501339">06 15 50 13 39</a></li>
                        <li><a href="https://wa.me/212609811095" target="_blank">WhatsApp</a></li>
                    </ul>
                </div>

            </div>

            <!-- Copyright bar -->
            <div class="footer-bottom">
                <p><?php echo $translation[$language]['copyright']; ?></p>
                <div class="footer-links">
                    <a href="#"><?php echo $translation[$language]['legal']; ?></a>
                    <a href="#"><?php echo $translation[$language]['privacy']; ?></a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Back to top -->
    <button class="back-top" id="backToTop" aria-label="<?php echo ($language === 'ar') ? 'العودة إلى الأعلى' : 'Retour en haut'; ?>">
        <i class="fa-solid fa-chevron-up footer-scroll-icon"></i>
    </button>

    <!-- WhatsApp floating button -->
    <a href="https://wa.me/212609811095" class="whatsapp-float" target="_blank" aria-label="<?php echo ($language === 'ar') ? 'تواصل عبر واتساب' : 'Contacter via WhatsApp'; ?>">
        <i class="fa-brands fa-whatsapp footer-whatsapp-icon"></i>
    </a>

    <script src="assets/js/main.js"></script>
</body>
</html>
