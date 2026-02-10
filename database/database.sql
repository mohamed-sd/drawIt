-- قاعدة بيانات منصة مسابقات الرسم
-- DrawIt Competition Platform Database

CREATE DATABASE IF NOT EXISTS drawit_competition CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE drawit_competition;

-- جدول الأدوار
CREATE TABLE roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- إضافة الأدوار الأساسية
INSERT INTO roles (name, description) VALUES 
('visitor', 'زائر عادي'),
('contestant', 'متسابق'),
('admin', 'مدير'),
('super_admin', 'مدير رئيسي');

-- جدول المستخدمين
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(100) NOT NULL,
    username VARCHAR(50) UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role_id INT NOT NULL DEFAULT 1,
    is_active BOOLEAN DEFAULT TRUE,
    profile_image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول المسابقات
CREATE TABLE competitions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(150) UNIQUE,
    description TEXT,
    rules TEXT,
    category VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول ربط المدراء بالمسابقات
CREATE TABLE competition_admins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    competition_id INT NOT NULL,
    admin_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_comp_admin (competition_id, admin_id),
    FOREIGN KEY (competition_id) REFERENCES competitions(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول اشتراكات المتسابقين في المسابقات
CREATE TABLE competition_contestants (
    id INT PRIMARY KEY AUTO_INCREMENT,
    competition_id INT NOT NULL,
    user_id INT NOT NULL,
    status ENUM('active', 'blocked') DEFAULT 'active',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_comp_contestant (competition_id, user_id),
    FOREIGN KEY (competition_id) REFERENCES competitions(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول المراحل
CREATE TABLE stages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    competition_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    stage_number INT NOT NULL,
    description TEXT,
    is_free_voting BOOLEAN DEFAULT TRUE,
    is_active BOOLEAN DEFAULT FALSE,
    start_date DATE,
    end_date DATE,
    max_qualifiers INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (competition_id) REFERENCES competitions(id) ON DELETE CASCADE,
    UNIQUE KEY unique_comp_stage (competition_id, stage_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- إنشاء مسابقة افتراضية
INSERT INTO competitions (name, slug, description, rules, category, is_active) VALUES
('مسابقة الرسم', 'drawing-competition', 'مسابقة لأفضل رسمة إبداعية', 'يجب أن يكون الفيديو واضحاً ويظهر خطوات العمل.', 'رسم', TRUE);

SET @default_competition_id = LAST_INSERT_ID();

-- إضافة المراحل الأساسية للمسابقة الافتراضية
INSERT INTO stages (competition_id, name, stage_number, description, is_free_voting, is_active, max_qualifiers) VALUES 
(@default_competition_id, 'المرحلة الأولى', 1, 'مشاركة مفتوحة مع تصويت مجاني', TRUE, TRUE, 20),
(@default_competition_id, 'المرحلة الثانية', 2, 'المرحلة النصف نهائية مع تصويت مدفوع', FALSE, FALSE, 10),
(@default_competition_id, 'المرحلة النهائية', 3, 'المرحلة النهائية - 3 متسابقين فقط', FALSE, FALSE, 3);

-- جدول الرسومات/الأعمال
CREATE TABLE drawings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    competition_id INT NOT NULL,
    user_id INT NOT NULL,
    stage_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    video_path VARCHAR(255) NOT NULL,
    thumbnail_path VARCHAR(255),
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    is_published BOOLEAN DEFAULT FALSE,
    is_qualified BOOLEAN DEFAULT FALSE,
    total_votes INT DEFAULT 0,
    total_paid_votes INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (competition_id) REFERENCES competitions(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (stage_id) REFERENCES stages(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول موافقات المدراء على الأعمال
CREATE TABLE admin_approvals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    drawing_id INT NOT NULL,
    admin_id INT NOT NULL,
    approval_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (drawing_id) REFERENCES drawings(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_approval (drawing_id, admin_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول موافقات المدراء على الترقيات
CREATE TABLE stage_qualifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    drawing_id INT NOT NULL,
    from_stage_id INT NOT NULL,
    to_stage_id INT NOT NULL,
    admin_id INT NOT NULL,
    approval_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (drawing_id) REFERENCES drawings(id) ON DELETE CASCADE,
    FOREIGN KEY (from_stage_id) REFERENCES stages(id),
    FOREIGN KEY (to_stage_id) REFERENCES stages(id),
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_qualification_approval (drawing_id, from_stage_id, to_stage_id, admin_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول التصويت
CREATE TABLE votes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    drawing_id INT NOT NULL,
    user_id INT NULL,
    voter_ip VARCHAR(45) NOT NULL,
    is_paid BOOLEAN DEFAULT FALSE,
    payment_id INT NULL,
    vote_type ENUM('free', 'paid') DEFAULT 'free',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (drawing_id) REFERENCES drawings(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول منع التصويت المكرر بالـ IP
CREATE TABLE vote_restrictions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    drawing_id INT NOT NULL,
    voter_ip VARCHAR(45) NOT NULL,
    stage_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (drawing_id) REFERENCES drawings(id) ON DELETE CASCADE,
    FOREIGN KEY (stage_id) REFERENCES stages(id),
    UNIQUE KEY unique_ip_vote (drawing_id, voter_ip, stage_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول المدفوعات
CREATE TABLE payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NULL,
    drawing_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50),
    transaction_id VARCHAR(100),
    status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (drawing_id) REFERENCES drawings(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول التنبيهات
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    drawing_id INT NULL,
    type ENUM('vote_received', 'drawing_approved', 'drawing_rejected', 'stage_qualified', 'stage_not_qualified', 'new_stage', 'winner', 'general') DEFAULT 'general',
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (drawing_id) REFERENCES drawings(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول الفائزين
CREATE TABLE winners (
    id INT PRIMARY KEY AUTO_INCREMENT,
    drawing_id INT NOT NULL,
    user_id INT NOT NULL,
    stage_id INT NOT NULL,
    position INT NOT NULL,
    prize_description TEXT,
    announced_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (drawing_id) REFERENCES drawings(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (stage_id) REFERENCES stages(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول الإعدادات العامة
CREATE TABLE settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    description VARCHAR(255),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- إضافة إعدادات أساسية
INSERT INTO settings (setting_key, setting_value, description) VALUES 
('site_name', 'DrawIt - منصة المسابقات الإبداعية', 'اسم الموقع'),
('vote_price', '5.00', 'سعر التصويت المدفوع'),
('competition_status', 'active', 'حالة المسابقة: active, paused, ended'),
('max_admins', '5', 'الحد الأقصى للمدراء'),
('current_stage', '1', 'المرحلة الحالية النشطة');

-- إنشاء مؤشرات لتحسين الأداء
CREATE INDEX idx_drawings_user ON drawings(user_id);
CREATE INDEX idx_drawings_stage ON drawings(stage_id);
CREATE INDEX idx_drawings_competition ON drawings(competition_id);
CREATE INDEX idx_stages_competition ON stages(competition_id);
CREATE INDEX idx_competition_admins ON competition_admins(competition_id);
CREATE INDEX idx_competition_contestants ON competition_contestants(competition_id);
CREATE INDEX idx_drawings_status ON drawings(status);
CREATE INDEX idx_votes_drawing ON votes(drawing_id);
CREATE INDEX idx_votes_ip ON votes(voter_ip);
CREATE INDEX idx_notifications_user ON notifications(user_id);
CREATE INDEX idx_notifications_read ON notifications(is_read);
