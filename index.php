<?php

// Page d'accueil du cabinet médical : affichage des informations et formulaire de réservation

$pageTitle = "Accueil";
require_once 'includes/header.php';

// Charger les services depuis la base de données
require_once 'config/Database.php';
$database = new DatabaseConnection();
$databaseConnection = $database->getConnection();
$allServices = $databaseConnection ? $databaseConnection->query("SELECT * FROM services")->fetchAll(PDO::FETCH_ASSOC) : [];

// Vérifier les erreurs du formulaire de réservation
$hasError = isset($_GET['status']) && $_GET['status'] === 'error';
$trackStatus = $_GET['track_status'] ?? '';
$trackFeedback = null;

// Gérer les retours de la recherche de rendez-vous
if ($trackStatus !== '') {
    $trackFeedbackMap = [
        'missing' => ['type' => 'error', 'message' => $translation[$language]['track_error_missing']],
        'not_found' => ['type' => 'error', 'message' => $translation[$language]['track_error_not_found']],
        'canceled' => ['type' => 'error', 'message' => $translation[$language]['track_error_canceled']],
    ];

    $trackFeedback = $trackFeedbackMap[$trackStatus] ?? null;
}
?>

<!-- Hero -->
<main>
    <section class="hero" id="hero">
        <div class="container">
            <div class="hero-grid">
                <div class="hero-text reveal">
                    <div class="hero-badge"><?php echo $translation[$language]['hero_badge']; ?></div>
                    <h1 class="hero-title"><?php echo $translation[$language]['hero_title']; ?></h1>
                    <p class="hero-desc"><?php echo $translation[$language]['hero_desc']; ?></p>
                    <div class="hero-actions">
                        <a href="#appointment" class="btn btn-primary btn-lg"><?php echo $translation[$language]['hero_cta_primary']; ?></a>
                        <a href="tel:+2120615501339" class="btn btn-secondary btn-lg"><?php echo $translation[$language]['hero_cta_secondary']; ?></a>
                    </div>
                </div>
                <div class="hero-mark">
                    <div class="hero-emblem">
                        <img src="assets/images/DoctorLogo.png" alt="Logo Dr. Dghar Mohamed" width="240" height="240">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About -->
    <section id="about" class="section-alt">
        <div class="container">
            <div class="about-grid">
                <div class="about-visual reveal-left">
                    <img src="assets/images/Doctor picture.png" alt="Dr. Dghar Mohamed" loading="lazy" width="800" height="600">
                </div>
                <div class="about-content reveal-right">
                    <h2 class="section-heading"><?php echo $translation[$language]['about_title']; ?></h2>
                    <p class="about-text"><?php echo $translation[$language]['about_text']; ?></p>
                    <dl class="about-metrics">
                        <div class="metric">
                            <dt><?php echo $translation[$language]['stat_years_label']; ?></dt>
                            <dd class="metric-value">8</dd>
                        </div>
                        <div class="metric">
                            <dt><?php echo $translation[$language]['stat_patients_label']; ?></dt>
                            <dd class="metric-value">1000+</dd>
                        </div>
                        <div class="metric">
                            <dt><?php echo $translation[$language]['stats_consultations_label']; ?></dt>
                            <dd class="metric-value">5000+</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </section>

    <!-- Services -->
    <section id="services">
        <div class="container">
            <div class="section-header reveal">
                <h2 class="section-heading"><?php echo $translation[$language]['services_title']; ?></h2>
                <p class="section-desc"><?php echo $translation[$language]['services_subtitle']; ?></p>
            </div>
            <div class="services-grid">
                <article class="service-card reveal">
                    <div class="service-icon"><i class="fa-solid fa-user-doctor"></i></div>
                    <div class="service-body">
                        <h3><?php echo $translation[$language]['s1_title']; ?></h3>
                        <p><?php echo $translation[$language]['s1_desc']; ?></p>
                    </div>
                </article>
                <article class="service-card reveal">
                    <div class="service-icon"><i class="fa-solid fa-notes-medical"></i></div>
                    <div class="service-body">
                        <h3><?php echo $translation[$language]['s2_title']; ?></h3>
                        <p><?php echo $translation[$language]['s2_desc']; ?></p>
                    </div>
                </article>
                <article class="service-card reveal">
                    <div class="service-icon"><i class="fa-solid fa-syringe"></i></div>
                    <div class="service-body">
                        <h3><?php echo $translation[$language]['s3_title']; ?></h3>
                        <p><?php echo $translation[$language]['s3_desc']; ?></p>
                    </div>
                </article>
                <article class="service-card reveal">
                    <div class="service-icon"><i class="fa-solid fa-heart-pulse"></i></div>
                    <div class="service-body">
                        <h3><?php echo $translation[$language]['s4_title']; ?></h3>
                        <p><?php echo $translation[$language]['s4_desc']; ?></p>
                    </div>
                </article>
                <article class="service-card reveal">
                    <div class="service-icon"><i class="fa-solid fa-baby"></i></div>
                    <div class="service-body">
                        <h3><?php echo $translation[$language]['s5_title']; ?></h3>
                        <p><?php echo $translation[$language]['s5_desc']; ?></p>
                    </div>
                </article>
                <article class="service-card reveal">
                    <div class="service-icon"><i class="fa-solid fa-apple-whole"></i></div>
                    <div class="service-body">
                        <h3><?php echo $translation[$language]['s6_title']; ?></h3>
                        <p><?php echo $translation[$language]['s6_desc']; ?></p>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <!-- Why Choose Us -->
    <section id="why" class="section-brand">
        <div class="container">
            <div class="section-header reveal">
                <h2 class="section-heading"><?php echo $translation[$language]['why_title']; ?></h2>
                <p class="section-desc"><?php echo $translation[$language]['why_subtitle']; ?></p>
            </div>
            <div class="why-grid">
                <div class="why-card reveal">
                    <div class="why-icon"><i class="fa-solid fa-clock"></i></div>
                    <h3 class="why-value" data-count="6/7">0</h3>
                    <p class="why-label"><?php echo $translation[$language]['w1_label']; ?></p>
                    <p class="why-desc"><?php echo $translation[$language]['w1_desc']; ?></p>
                </div>
                <div class="why-card reveal">
                    <div class="why-icon"><i class="fa-solid fa-award"></i></div>
                    <h3 class="why-value" data-count="100%">0</h3>
                    <p class="why-label"><?php echo $translation[$language]['w2_label']; ?></p>
                    <p class="why-desc"><?php echo $translation[$language]['w2_desc']; ?></p>
                </div>
                <div class="why-card reveal">
                    <div class="why-icon"><i class="fa-solid fa-location-dot"></i></div>
                    <h3 class="why-value" data-count="&mdash;">&mdash;</h3>
                    <p class="why-label"><?php echo $translation[$language]['w3_label']; ?></p>
                    <p class="why-desc"><?php echo $translation[$language]['w3_desc']; ?></p>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials -->
    <section id="testimonials" class="section-alt">
        <div class="container">
            <div class="section-header reveal">
                <h2 class="section-heading"><?php echo $translation[$language]['testimonials_title']; ?></h2>
                <p class="section-desc"><?php echo $translation[$language]['testimonials_subtitle']; ?></p>
            </div>
            <div class="testimonials-grid">
                <article class="testimonial-card reveal">
                    <div class="testimonial-stars">
                        <i class="fa-solid fa-star" style="color:#C4A052;"></i>
                        <i class="fa-solid fa-star" style="color:#C4A052;"></i>
                        <i class="fa-solid fa-star" style="color:#C4A052;"></i>
                        <i class="fa-solid fa-star" style="color:#C4A052;"></i>
                        <i class="fa-solid fa-star" style="color:#C4A052;"></i>
                    </div>
                    <p class="testimonial-text"><?php echo $translation[$language]['t1_text']; ?></p>
                    <div class="testimonial-author">
                        <span class="author-initial">Y</span>
                        <cite><?php echo $translation[$language]['t1_author']; ?></cite>
                    </div>
                </article>
                <article class="testimonial-card reveal">
                    <div class="testimonial-stars">
                        <i class="fa-solid fa-star" style="color:#C4A052;"></i>
                        <i class="fa-solid fa-star" style="color:#C4A052;"></i>
                        <i class="fa-solid fa-star" style="color:#C4A052;"></i>
                        <i class="fa-solid fa-star" style="color:#C4A052;"></i>
                        <i class="fa-solid fa-star" style="color:#C4A052;"></i>
                    </div>
                    <p class="testimonial-text"><?php echo $translation[$language]['t2_text']; ?></p>
                    <div class="testimonial-author">
                        <span class="author-initial">L</span>
                        <cite><?php echo $translation[$language]['t2_author']; ?></cite>
                    </div>
                </article>
                <article class="testimonial-card reveal">
                    <div class="testimonial-stars">
                        <i class="fa-solid fa-star" style="color:#C4A052;"></i>
                        <i class="fa-solid fa-star" style="color:#C4A052;"></i>
                        <i class="fa-solid fa-star" style="color:#C4A052;"></i>
                        <i class="fa-solid fa-star" style="color:#C4A052;"></i>
                        <i class="fa-solid fa-star" style="color:#C4A052;"></i>
                    </div>
                    <p class="testimonial-text"><?php echo $translation[$language]['t3_text']; ?></p>
                    <div class="testimonial-author">
                        <span class="author-initial">M</span>
                        <cite><?php echo $translation[$language]['t3_author']; ?></cite>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <!-- Track appointment -->
    <section id="track-appointment" class="section-track section-alt">
        <div class="container">
            <div class="track-layout">
                <div class="track-copy reveal-left">
                    <span class="track-kicker"><?php echo $translation[$language]['track_kicker']; ?></span>
                    <h2 class="section-heading"><?php echo $translation[$language]['track_title']; ?></h2>
                    <p class="track-note"><?php echo $translation[$language]['track_subtitle']; ?></p>
                    <p class="track-support"><?php echo $translation[$language]['track_note']; ?></p>
                </div>

                <div class="track-panel reveal-right">
                    <div class="track-panel-head">
                        <div class="track-panel-icon"><i class="fa-solid fa-ticket"></i></div>
                        <div>
                            <h3><?php echo $translation[$language]['track_title']; ?></h3>
                            <p><?php echo $translation[$language]['track_subtitle']; ?></p>
                        </div>
                    </div>

                    <?php if ($trackFeedback): ?>
                        <div class="form-feedback <?php echo htmlspecialchars($trackFeedback['type']); ?>"><?php echo htmlspecialchars($trackFeedback['message']); ?></div>
                    <?php endif; ?>

                    <form action="track-appointment.php" method="post" class="track-form">
                        <div class="track-form-grid">
                            <div class="form-group">
                                <input type="text" name="reference_number" class="form-input" placeholder=" " required>
                                <label><?php echo $translation[$language]['track_reference_label']; ?></label>
                            </div>
                            <div class="form-group">
                                <input type="text" name="cni" class="form-input" placeholder=" " required>
                                <label><?php echo $translation[$language]['track_cni_label']; ?></label>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block"><?php echo $translation[$language]['track_submit']; ?></button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Booking -->
    <section id="appointment">
        <div class="container">
            <div class="appointment-grid">

                <!-- Contact info -->
                <div class="appointment-info reveal-left" id="contact">
                    <h2 class="section-heading"><?php echo $translation[$language]['appointment_title']; ?></h2>
                    <p class="appointment-subtitle"><?php echo $translation[$language]['appointment_subtitle']; ?></p>

                    <div class="contact-list">
                        <a href="tel:+212615501339" class="contact-card">
                            <span class="contact-icon contact-icon-phone"><i class="fa-solid fa-phone"></i></span>
                            <span class="contact-body">
                                <span class="contact-label"><?php echo $translation[$language]['contact_phone']; ?></span>
                                <span class="contact-value">06 15 50 13 39</span>
                            </span>
                        </a>
                        <a href="https://wa.me/212609811095" target="_blank" class="contact-card">
                            <span class="contact-icon contact-icon-whatsapp"><i class="fa-brands fa-whatsapp"></i></span>
                            <span class="contact-body">
                                <span class="contact-label"><?php echo $translation[$language]['contact_whatsapp']; ?></span>
                                <span class="contact-value"><?php echo $translation[$language]['contact_whatsapp_sub']; ?></span>
                            </span>
                        </a>
                        <a href="https://www.google.com/maps?q=Tanger,+Morocco" target="_blank" class="contact-card">
                            <span class="contact-icon contact-icon-location"><i class="fa-solid fa-location-dot"></i></span>
                            <span class="contact-body">
                                <span class="contact-label"><?php echo $translation[$language]['contact_address']; ?></span>
                                <span class="contact-value"><?php echo $translation[$language]['contact_address_val']; ?></span>
                            </span>
                        </a>
                    </div>

                    <div class="map">
                        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d206847.46467389283!2d-5.952525164871957!3d35.74100587652758!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0xd0b875cf04c132d%3A0x76bfc571bfb4e17a!2sTangier%2C%20Morocco!5e0!3m2!1sen!2sma!4v1778541217538!5m2!1sen!2sma" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy" title="Localisation du cabinet à Tanger"></iframe>
                    </div>
                </div>

                <!-- Booking form -->
                <div class="appointment-form reveal-right">
                    <div class="form-header">
                        <h3><?php echo $translation[$language]['form_title']; ?></h3>
                        <p><?php echo $translation[$language]['form_note']; ?></p>
                    </div>

                    <?php if ($hasError): ?>
                        <div class="form-feedback error"><?php echo $translation[$language]['form_error']; ?></div>
                        <?php if (isset($_SESSION['form_errors']) && count($_SESSION['form_errors']) > 0): ?>
                            <ul class="error-list">
                                <?php foreach ($_SESSION['form_errors'] as $errorMessage): ?>
                                    <li><?php echo htmlspecialchars($errorMessage); ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <?php unset($_SESSION['form_errors']); ?>
                        <?php endif; ?>
                    <?php endif; ?>

                    <form action="traitement/submit-appointment.php" method="post" enctype="multipart/form-data" id="appointmentForm">
                        <div class="form-group">
                            <input type="text" name="name" class="form-input" placeholder=" " required>
                            <label><?php echo $translation[$language]['name_label']; ?></label>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <input type="email" name="email" class="form-input" placeholder=" " required>
                                <label><?php echo $translation[$language]['email_label']; ?></label>
                            </div>
                            <div class="form-group">
                                <input type="tel" name="phone" class="form-input" placeholder=" " required>
                                <label><?php echo $translation[$language]['phone_label']; ?></label>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <input type="text" name="cni" class="form-input" placeholder=" " required>
                                <label><?php echo $translation[$language]['cni_label']; ?></label>
                            </div>
                            <div class="form-group">
                                <select name="service_id" class="form-input" required id="serviceSelect">
                                    <option value="" disabled selected></option>
                                    <?php foreach ($allServices as $service): ?>
                                        <?php 
                                            $srvName = $service['name'];
                                            $tKey = 'srv_' . $srvName;
                                            $displayName = isset($translation[$language][$tKey]) ? $translation[$language][$tKey] : $srvName;
                                        ?>
                                        <option value="<?php echo $service['id']; ?>"><?php echo htmlspecialchars($displayName); ?> (<?php echo number_format($service['price'], 2); ?> DH)</option>
                                    <?php endforeach; ?>
                                </select>
                                <label><?php echo $translation[$language]['service_label']; ?></label>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <input type="date" name="appointment_date" class="form-input" placeholder=" " required id="appointmentDate">
                                <label><?php echo $translation[$language]['date_label']; ?></label>
                            </div>
                            <div class="form-group-slots" style="margin-bottom: var(--space-md); position: relative;">
                                <div id="time-slots-container">
                                    <p style="color:#999;font-size:13px;padding-top:8px;"><?php echo $translation[$language]['select_date_first']; ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <input type="file" name="medical_document" class="form-input" accept=".pdf,.jpg,.jpeg,.png">
                            <label><?php echo $translation[$language]['file_label']; ?></label>
                        </div>
                        <div class="form-group">
                            <textarea name="message" class="form-input form-textarea" placeholder=" " rows="4"></textarea>
                            <label><?php echo $translation[$language]['message_label']; ?></label>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block"><?php echo $translation[$language]['submit']; ?></button>
                    </form>
                </div>

            </div>
        </div>
    </section>
</main>

<?php require_once 'includes/footer.php'; ?>
