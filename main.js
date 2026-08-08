// JavaScript for Interactivity
        // Mobile Menu Toggle
        const menuBtn = document.getElementById('menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');

        menuBtn.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
        });

        // Squad Filtering Logic
        function filterSquad(position) {
            const cards = document.querySelectorAll('.player-card');
            const tabs = document.querySelectorAll('.squad-tab');

            // Update active tab styles
            tabs.forEach(tab => {
                tab.classList.remove('bg-clubYellow', 'text-black');
                tab.classList.add('bg-gray-800', 'text-gray-300');
            });
            event.target.classList.remove('bg-gray-800', 'text-gray-300');
            event.target.classList.add('bg-clubYellow', 'text-black');

            cards.forEach(card => {
                if (position === 'all' || card.getAttribute('data-position') === position) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        // Gallery Carousel
        const galleryTrack = document.getElementById('gallery-track');
        const galleryCards = document.querySelectorAll('.gallery-card');
        const galleryPrev = document.getElementById('gallery-prev');
        const galleryNext = document.getElementById('gallery-next');
        const galleryDots = document.getElementById('gallery-dots');
        let galleryIndex = 0;
        let galleryStartX = 0;

        function updateGallery(index) {
            if (!galleryCards.length) return;

            galleryIndex = (index + galleryCards.length) % galleryCards.length;
            galleryCards[galleryIndex].scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'start' });

            const dots = document.querySelectorAll('.gallery-dot');
            dots.forEach((dot, dotIndex) => {
                dot.className = `gallery-dot h-2.5 w-2.5 rounded-full transition ${dotIndex === galleryIndex ? 'bg-clubYellow' : 'bg-gray-600'}`;
            });
        }

        if (galleryTrack && galleryPrev && galleryNext && galleryDots) {
            galleryCards.forEach((_, index) => {
                const dot = document.createElement('button');
                dot.type = 'button';
                dot.className = 'gallery-dot h-2.5 w-2.5 rounded-full transition bg-gray-600';
                dot.setAttribute('aria-label', `Go to gallery slide ${index + 1}`);
                dot.addEventListener('click', () => updateGallery(index));
                galleryDots.appendChild(dot);
            });

            updateGallery(0);
            galleryPrev.addEventListener('click', () => updateGallery(galleryIndex - 1));
            galleryNext.addEventListener('click', () => updateGallery(galleryIndex + 1));

            galleryTrack.addEventListener('touchstart', (e) => {
                galleryStartX = e.touches[0].clientX;
            });

            galleryTrack.addEventListener('touchend', (e) => {
                const touchEndX = e.changedTouches[0].clientX;
                if (galleryStartX - touchEndX > 50) {
                    updateGallery(galleryIndex + 1);
                } else if (touchEndX - galleryStartX > 50) {
                    updateGallery(galleryIndex - 1);
                }
            });
        }

        // Contact Form Handler
        const contactForm = document.getElementById('contact-form');
        const successBox = document.getElementById('form-success');
        const submitBtn = document.getElementById('submit-btn');
        const nameInput = document.getElementById('contact-name');
        const emailInput = document.getElementById('contact-email');
        const messageInput = document.getElementById('contact-message');
        const nameError = document.getElementById('name-error');
        const emailError = document.getElementById('email-error');
        const messageError = document.getElementById('message-error');
        const contactEmail = 'boldit2015@gmail.com';

        function updateFeedback(message, type = 'success') {
            successBox.className = `mt-4 p-4 rounded-xl text-center text-sm font-bold ${type === 'success' ? 'bg-green-500/10 border border-green-500/30 text-green-400' : 'bg-red-500/10 border border-red-500/30 text-red-400'}`;
            successBox.textContent = message;
            successBox.classList.remove('hidden');
        }

        function validateField(input, errorEl, validator) {
            const isValid = validator(input.value.trim());
            input.classList.toggle('border-red-500', !isValid);
            input.classList.toggle('border-gray-800', isValid);
            errorEl.classList.toggle('hidden', isValid);
            return isValid;
        }

        function validateForm() {
            const isNameValid = validateField(nameInput, nameError, (value) => value.length >= 2);
            const isEmailValid = validateField(emailInput, emailError, (value) => /.+@.+\..+/.test(value) || /^\+?[0-9\s-]{7,15}$/.test(value));
            const isMessageValid = validateField(messageInput, messageError, (value) => value.length >= 10);
            submitBtn.disabled = !(isNameValid && isEmailValid && isMessageValid);
            return isNameValid && isEmailValid && isMessageValid;
        }

        [nameInput, emailInput, messageInput].forEach((input) => {
            input.addEventListener('input', validateForm);
        });

        if (contactForm) {
            contactForm.addEventListener('submit', async function (e) {
                e.preventDefault();
                if (!validateForm()) return;

                submitBtn.disabled = true;
                submitBtn.textContent = 'Sending...';
                successBox.className = 'hidden mt-4 p-4 rounded-xl text-center text-sm font-bold';

                const formData = new FormData(contactForm);

                try {
                    const response = await fetch('php/contact.php', {
                        method: 'POST',
                        body: formData
                    });

                    const responseText = await response.text();
                    let result = {};
                    try {
                        result = JSON.parse(responseText);
                    } catch (error) {
                        result = { success: false, message: responseText || 'Unable to send message.' };
                    }

                    if (result.success) {
                        updateFeedback(result.message || 'Thank you! Your message has been sent.', 'success');
                        contactForm.reset();
                        validateForm();
                    } else {
                        updateFeedback(result.message || 'Unable to send message right now.', 'error');
                    }
                } catch (error) {
                    updateFeedback('Unable to reach the server right now. Please try again later.', 'error');
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Send Message';
                }
            });
        }