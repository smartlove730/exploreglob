// GSAP Animations for Blog Platform

// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', function() {
    
    // Register GSAP ScrollTrigger plugin
    if (typeof gsap !== 'undefined' && gsap.registerPlugin) {
        gsap.registerPlugin(ScrollTrigger);
    }

    // Navbar scroll effect
    const navbar = document.querySelector('.navbar');
    if (navbar) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) {
                navbar.style.boxShadow = '0 4px 20px rgba(0, 0, 0, 0.1)';
                navbar.style.padding = '0.5rem 0';
            } else {
                navbar.style.boxShadow = '0 2px 8px rgba(0, 0, 0, 0.08)';
                navbar.style.padding = '1rem 0';
            }
        });
    }

    // Animate cards on page load
    const animatedCards = document.querySelectorAll('.animated-card');
    if (animatedCards.length > 0 && typeof gsap !== 'undefined') {
        gsap.fromTo(animatedCards, 
            {
                opacity: 0,
                y: 50,
                scale: 0.9
            },
            {
                opacity: 1,
                y: 0,
                scale: 1,
                duration: 0.8,
                stagger: 0.1,
                ease: 'power3.out'
            }
        );
    }

    // Animate category cards
    const categoryCards = document.querySelectorAll('.category-card');
    if (categoryCards.length > 0 && typeof gsap !== 'undefined') {
        gsap.fromTo(categoryCards,
            {
                opacity: 0,
                scale: 0.8,
                rotation: -5
            },
            {
                opacity: 1,
                scale: 1,
                rotation: 0,
                duration: 0.6,
                stagger: 0.1,
                ease: 'back.out(1.7)'
            }
        );
    }

    // Hero section animation
    const heroTitle = document.querySelector('.hero-title');
    const heroSubtitle = document.querySelector('.hero-subtitle');
    
    if (heroTitle && typeof gsap !== 'undefined') {
        gsap.fromTo(heroTitle,
            {
                opacity: 0,
                y: -50,
                scale: 0.8
            },
            {
                opacity: 1,
                y: 0,
                scale: 1,
                duration: 1,
                ease: 'power3.out'
            }
        );
    }

    if (heroSubtitle && typeof gsap !== 'undefined') {
        gsap.fromTo(heroSubtitle,
            {
                opacity: 0,
                y: 20
            },
            {
                opacity: 1,
                y: 0,
                duration: 1,
                delay: 0.3,
                ease: 'power2.out'
            }
        );
    }

    // Blog sections scroll animation
    const blogSections = document.querySelectorAll('.blog-section');
    if (blogSections.length > 0 && typeof gsap !== 'undefined' && typeof ScrollTrigger !== 'undefined') {
        blogSections.forEach((section, index) => {
            gsap.fromTo(section,
                {
                    opacity: 0,
                    y: 50
                },
                {
                    opacity: 1,
                    y: 0,
                    duration: 0.8,
                    scrollTrigger: {
                        trigger: section,
                        start: 'top 80%',
                        end: 'bottom 20%',
                        toggleActions: 'play none none none'
                    },
                    ease: 'power2.out'
                }
            );
        });
    }

    // Blog cover image animation
    const blogCoverImage = document.querySelector('.blog-cover-image');
    if (blogCoverImage && typeof gsap !== 'undefined') {
        gsap.fromTo(blogCoverImage,
            {
                opacity: 0,
                scale: 1.1
            },
            {
                opacity: 1,
                scale: 1,
                duration: 1.2,
                ease: 'power2.out'
            }
        );
    }

    // Related blog cards animation
    const relatedBlogCards = document.querySelectorAll('.related-blog-card');
    if (relatedBlogCards.length > 0 && typeof gsap !== 'undefined') {
        if (typeof ScrollTrigger !== 'undefined') {
            gsap.fromTo(relatedBlogCards,
                {
                    opacity: 0,
                    y: 40,
                    scale: 0.9
                },
                {
                    opacity: 1,
                    y: 0,
                    scale: 1,
                    duration: 0.6,
                    stagger: 0.15,
                    scrollTrigger: {
                        trigger: '.related-blogs-section',
                        start: 'top 70%',
                        toggleActions: 'play none none none'
                    },
                    ease: 'power2.out'
                }
            );
        } else {
            // Fallback without ScrollTrigger
            gsap.fromTo(relatedBlogCards,
                {
                    opacity: 0,
                    y: 40,
                    scale: 0.9
                },
                {
                    opacity: 1,
                    y: 0,
                    scale: 1,
                    duration: 0.6,
                    stagger: 0.15,
                    delay: 0.3,
                    ease: 'power2.out'
                }
            );
        }
    }

    // Hashtags animation
    const hashtags = document.querySelectorAll('.hashtag');
    if (hashtags.length > 0 && typeof gsap !== 'undefined') {
        gsap.fromTo(hashtags,
            {
                opacity: 0,
                scale: 0,
                rotation: -180
            },
            {
                opacity: 1,
                scale: 1,
                rotation: 0,
                duration: 0.5,
                stagger: 0.05,
                ease: 'back.out(1.7)'
            }
        );
    }

    // Button hover effects
    const buttons = document.querySelectorAll('.btn-primary');
    buttons.forEach(button => {
        button.addEventListener('mouseenter', function() {
            if (typeof gsap !== 'undefined') {
                gsap.to(this, {
                    scale: 1.05,
                    duration: 0.3,
                    ease: 'power2.out'
                });
            }
        });
        
        button.addEventListener('mouseleave', function() {
            if (typeof gsap !== 'undefined') {
                gsap.to(this, {
                    scale: 1,
                    duration: 0.3,
                    ease: 'power2.out'
                });
            }
        });
    });

    // Section title animation
    const sectionTitles = document.querySelectorAll('.section-title');
    if (sectionTitles.length > 0 && typeof gsap !== 'undefined') {
        if (typeof ScrollTrigger !== 'undefined') {
            sectionTitles.forEach(title => {
                gsap.fromTo(title,
                    {
                        opacity: 0,
                        y: -30
                    },
                    {
                        opacity: 1,
                        y: 0,
                        duration: 0.8,
                        scrollTrigger: {
                            trigger: title,
                            start: 'top 85%',
                            toggleActions: 'play none none none'
                        },
                        ease: 'power2.out'
                    }
                );
            });
        }
    }

    // Parallax effect for hero section
    const heroSection = document.querySelector('.hero-section');
    if (heroSection && typeof gsap !== 'undefined') {
        window.addEventListener('scroll', function() {
            const scrolled = window.pageYOffset;
            if (scrolled < heroSection.offsetHeight) {
                gsap.to(heroSection, {
                    y: scrolled * 0.5,
                    duration: 0.3,
                    ease: 'none'
                });
            }
        });
    }

    // Image loading error handler - replace with SVG placeholder (no external requests)
    const images = document.querySelectorAll('img');
    images.forEach(img => {
        // #region agent log
        let errorHandled = false;
        // #endregion
        
        img.addEventListener('error', function(e) {
            // #region agent log
            fetch('http://127.0.0.1:7242/ingest/ffb9fee4-cb58-4096-a251-97bedfe37eac',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'animations.js:277',message:'Image error handler triggered',data:{src:this.src,hasPlaceholderClass:this.classList.contains('image-placeholder'),errorHandled:errorHandled},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'A'})}).catch(()=>{});
            // #endregion
            
            // Prevent infinite loops
            if (errorHandled || this.classList.contains('image-placeholder')) {
                // #region agent log
                fetch('http://127.0.0.1:7242/ingest/ffb9fee4-cb58-4096-a251-97bedfe37eac',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'animations.js:284',message:'Error already handled, skipping',data:{errorHandled:errorHandled},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'B'})}).catch(()=>{});
                // #endregion
                return;
            }
            
            errorHandled = true;
            
            // Use inline SVG as fallback - no external request needed
            const width = this.naturalWidth || this.width || 800;
            const height = this.naturalHeight || this.height || 600;
            const text = this.alt || 'Image';
            const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="${width}" height="${height}"><rect fill="#6366f1" width="100%" height="100%"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="#ffffff" font-family="Arial,sans-serif" font-size="${Math.min(width, height) / 15}" font-weight="600">${text}</text></svg>`;
            const svgDataUri = `data:image/svg+xml,${encodeURIComponent(svg)}`;
            
            // #region agent log
            fetch('http://127.0.0.1:7242/ingest/ffb9fee4-cb58-4096-a251-97bedfe37eac',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'animations.js:296',message:'Applying SVG placeholder',data:{width:width,height:height,text:text},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'C'})}).catch(()=>{});
            // #endregion
            
            this.src = svgDataUri;
            this.classList.add('image-placeholder');
        }, { once: true }); // Use once option to prevent multiple handlers
    });

    // Card hover effects with GSAP
    const cards = document.querySelectorAll('.animated-card, .related-blog-card');
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            if (typeof gsap !== 'undefined') {
                gsap.to(this, {
                    y: -10,
                    scale: 1.02,
                    duration: 0.4,
                    ease: 'power2.out'
                });
            }
        });
        
        card.addEventListener('mouseleave', function() {
            if (typeof gsap !== 'undefined') {
                gsap.to(this, {
                    y: 0,
                    scale: 1,
                    duration: 0.4,
                    ease: 'power2.out'
                });
            }
        });
    });

    console.log('Animations initialized successfully!');
});
