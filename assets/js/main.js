/**
 * DrawIt Competition Platform - Main JavaScript
 * السكريبتات الرئيسية
 */

// تفعيل tooltips
document.addEventListener('DOMContentLoaded', function() {
    // Bootstrap tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Auto-hide alerts
    setTimeout(function() {
        var alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
});

// تأكيد الحذف
function confirmDelete(message) {
    return confirm(message || 'هل أنت متأكد من الحذف؟');
}

// Preview صورة قبل الرفع
function previewImage(input, previewId) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById(previewId).src = e.target.result;
            document.getElementById(previewId).style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Preview فيديو قبل الرفع
function previewVideo(input, previewId) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var video = document.getElementById(previewId);
            video.src = e.target.result;
            video.style.display = 'block';
            video.load();
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// عداد الأحرف للنصوص
function countCharacters(textareaId, counterId, maxLength) {
    var textarea = document.getElementById(textareaId);
    var counter = document.getElementById(counterId);
    
    textarea.addEventListener('input', function() {
        var length = this.value.length;
        counter.textContent = length + ' / ' + maxLength;
        
        if (length >= maxLength) {
            counter.classList.add('text-danger');
        } else {
            counter.classList.remove('text-danger');
        }
    });
}

// تحديث الوقت بشكل ديناميكي
function updateTimeAgo() {
    var timeElements = document.querySelectorAll('.time-ago');
    timeElements.forEach(function(element) {
        var timestamp = element.getAttribute('data-timestamp');
        if (timestamp) {
            element.textContent = getTimeAgo(timestamp);
        }
    });
}

function getTimeAgo(timestamp) {
    var now = new Date();
    var past = new Date(timestamp);
    var diff = Math.floor((now - past) / 1000);
    
    if (diff < 60) return 'الآن';
    if (diff < 3600) return 'منذ ' + Math.floor(diff / 60) + ' دقيقة';
    if (diff < 86400) return 'منذ ' + Math.floor(diff / 3600) + ' ساعة';
    if (diff < 604800) return 'منذ ' + Math.floor(diff / 86400) + ' يوم';
    
    return past.toLocaleDateString('ar-SA');
}

// AJAX للتصويت
function vote(drawingId, isPaid = false) {
    var btn = event.target;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري التصويت...';
    
    fetch('ajax/vote.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            drawing_id: drawingId,
            is_paid: isPaid
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message);
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-heart"></i> صوّت';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('حدث خطأ أثناء التصويت');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-heart"></i> صوّت';
    });
}

// تحديث التنبيهات
function loadNotifications() {
    fetch('ajax/get_notifications.php')
    .then(response => response.json())
    .then(data => {
        if (data.success && data.unread_count > 0) {
            var badges = document.querySelectorAll('.notification-badge');
            badges.forEach(function(badge) {
                badge.textContent = data.unread_count;
                badge.style.display = 'inline-block';
            });
        }
    })
    .catch(error => console.error('Error:', error));
}

// تحديث التنبيهات كل 30 ثانية
setInterval(loadNotifications, 30000);

// تفعيل Smooth Scroll
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        var target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Lazy Loading للفيديوهات
if ('IntersectionObserver' in window) {
    var videoObserver = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                var video = entry.target;
                video.src = video.dataset.src;
                video.load();
                videoObserver.unobserve(video);
            }
        });
    });

    document.querySelectorAll('video[data-src]').forEach(function(video) {
        videoObserver.observe(video);
    });
}

// Form Validation Enhancement
(function() {
    'use strict';
    var forms = document.querySelectorAll('.needs-validation');
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
})();
