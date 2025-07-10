<?php
// 处理用户提交的数据
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content'])) {
    $content = trim($_POST['content']);
    
    if (!empty($content)) {
        // 写入内容到文件（追加模式）
        $file = fopen('douyin_link_list.txt', 'a');
        if ($file) {
            fwrite($file, $content . "\n");
            fclose($file);

            // 定义要检查和创建的目录结构
            $directories = [
                'item/content/cover',
                'item/content/images',
                'item/content/music',
                'item/content/music_cover',
                'item/content/video',
                'item/images/avatar',
                'item/images/background',
                'item/images/comment',
                'item/meme'
            ];

            // 检查并创建目录
            foreach ($directories as $dir) {
                if (!is_dir($dir)) {
                    mkdir($dir, 0777, true);
                }
            }
            
            // 启动Python脚本
            $pythonScript = 'douyin_clone.py';
            exec("python3 $pythonScript > /dev/null 2>&1 &");
            
            $message = [
                'type' => 'success',
                'text' => '内容已写入并启动脚本成功！'
            ];
        } else {
            $message = [
                'type' => 'error',
                'text' => '无法打开文件，请检查权限！'
            ];
        }
    } else {
        $message = [
            'type' => 'warning',
            'text' => '请输入内容后再提交！'
        ];
    }
}

// 页面配置（统一哔抖项目风格）
$pageConfig = [
    'title' => '克隆工具 - 哔抖',
    'keywords' => '哔抖,克隆工具,视频处理',
    'description' => '哔抖平台内容克隆工具，支持批量处理'
];

// 检查是否为移动端访问
$isMobile = preg_match('/(android|iphone|ipad|ipod)/i', $_SERVER['HTTP_USER_AGENT']);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link rel="icon" href="./favicon.ico" type="image/x-icon">
    <title><?php echo htmlspecialchars($pageConfig['title']); ?></title>
    <meta name="keywords" content="<?php echo htmlspecialchars($pageConfig['keywords']); ?>">
    <meta name="description" content="<?php echo htmlspecialchars($pageConfig['description']); ?>">
    <!-- Tailwind CSS -->
    <script src="./lib/3.4.16"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="./lib/font-awesome.min.css">
    <!-- 配置Tailwind（保持哔抖项目统一样式） -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#84a2fe', // 哔抖主题色
                        secondary: '#23ADE5', // 哔抖辅助色
                        dark: '#18191C',
                        light: '#F4F4F4',
                        'text-primary': '#222222',
                        'text-secondary': '#666666',
                        'text-tertiary': '#999999',
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                    },
                    boxShadow: {
                        'card': '0 2px 10px rgba(0, 0, 0, 0.05)',
                        'hover': '0 4px 15px rgba(0, 0, 0, 0.08)',
                    }
                },
            }
        }
    </script>
    <style type="text/tailwindcss">
        @layer utilities {
            .content-auto {
                content-visibility: auto;
            }
            .scrollbar-hide {
                -ms-overflow-style: none;
                scrollbar-width: none;
            }
            .scrollbar-hide::-webkit-scrollbar {
                display: none;
            }
            .animate-fade-in {
                animation: fadeIn 0.5s ease-in-out;
            }
            .animate-slide-up {
                animation: slideUp 0.6s ease-out;
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        /* 移动端优化样式 */
        @media (max-width: 640px) {
            .mobile-input {
                min-height: 180px !important;
            }
            .btn-mobile {
                padding: 12px 0 !important;
                font-size: 16px !important;
            }
        }
    </style>
</head>
<body class="bg-gray-50 font-sans text-text-primary">
    <!-- 顶部导航栏（与哔抖主站保持一致） -->
    <header class="fixed top-0 left-0 right-0 bg-white/95 backdrop-blur-sm z-50 shadow-sm transition-all duration-300" id="header">
        <div class="container mx-auto px-4 py-3 flex items-center justify-between">
            <!-- Logo -->
            <div class="flex items-center space-x-4">
                <a href="./" class="flex items-center">
                    <img src="./favicon.ico" alt="哔抖图标" class="w-8 h-8 text-primary">
                    <span class="mx-2"></span>
                    <span class="text-xl font-bold text-primary hidden sm:inline-block">哔抖</span> 
                </a>
                
                <!-- 主导航 -->
                <nav class="hidden md:flex items-center space-x-6">
                    <a href="./" class="text-text-secondary hover:text-primary transition-colors">首页</a>
                    <a href="javascript:void(0)" class="text-text-secondary hover:text-primary transition-colors">内容</a>
                    <a href="./clone.php" class="text-primary font-medium hover:text-primary/80 transition-colors">克隆</a>
                </nav>
            </div>
            
            <!-- 用户操作区 -->
            <div class="flex items-center space-x-4">
                <button class="hidden sm:block text-text-secondary hover:text-primary transition-colors">
                    <i class="fa fa-message text-xl"></i>
                </button>
                <button class="hidden sm:block text-text-secondary hover:text-primary transition-colors">
                    <i class="fa fa-bell text-xl"></i>
                </button>
                <div class="relative">
                    <button class="w-8 h-8 rounded-full bg-gray-200 overflow-hidden border-2 border-transparent hover:border-primary transition-all">
                        <img src="./favicon.ico" alt="用户头像" class="w-full h-full object-cover" />
                    </button>
                </div>
                <button class="md:hidden text-text-secondary">
                    <i class="fa fa-bars text-xl"></i>
                </button>
            </div>
        </div>
    </header>

    <!-- 主内容区 -->
    <main class="container mx-auto px-4 pt-20 pb-16 sm:pt-24">
        <div class="max-w-3xl mx-auto w-full">
            <!-- 页面标题（移动端优化） -->
            <div class="text-center mb-6 sm:mb-10 animate-fade-in">
                <h1 class="text-[clamp(1.5rem,5vw,2.2rem)] font-bold text-text-primary mb-3">内容克隆工具</h1>
                <p class="text-text-secondary text-sm sm:text-base px-2">输入链接批量克隆内容，自动同步到本地</p>
            </div>
            
            <!-- 消息提示 -->
            <?php if (isset($message)): ?>
                <div class="mb-6 p-4 rounded-lg animate-slide-up flex items-center space-x-3 <?php 
                    echo $message['type'] === 'success' ? 'bg-green-50 text-green-700 border border-green-200' : 
                         ($message['type'] === 'error' ? 'bg-red-50 text-red-700 border border-red-200' : 
                         'bg-yellow-50 text-yellow-700 border border-yellow-200');
                ?>">
                    <i class="fa <?php 
                        echo $message['type'] === 'success' ? 'fa-check-circle' : 
                             ($message['type'] === 'error' ? 'fa-times-circle' : 'fa-exclamation-circle');
                    ?> text-xl"></i>
                    <span><?php echo htmlspecialchars($message['text']); ?></span>
                </div>
            <?php endif; ?>
            
            <!-- 表单卡片（增强移动端体验） -->
            <div class="bg-white rounded-xl shadow-card p-5 sm:p-6 md:p-8 animate-slide-up" style="animation-delay: 0.2s;">
                <form method="post" class="space-y-5">
                    <div>
                        <label for="content" class="block text-text-primary font-medium mb-2 text-sm sm:text-base">输入内容</label>
                        <textarea 
                            id="content" 
                            name="content" 
                            rows="6" 
                            class="mobile-input w-full px-4 py-3 rounded-lg border border-gray-200 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary transition-all resize-none text-sm"
                            placeholder="请输入链接，每行一个...（其实多个也能处理🌚）"></textarea>
                        <p class="text-text-tertiary text-xs sm:text-sm mt-2">支持批量输入，系统将自动处理并保存</p>
                    </div>
                    
                    <div class="flex flex-col sm:flex-row gap-3 sm:gap-4">
                        <button 
                            type="submit" 
                            class="btn-mobile flex-1 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors shadow-sm flex items-center justify-center space-x-2 font-medium"
                        >
                            <i class="fa fa-clone"></i>
                            <span>开始克隆</span>
                        </button>
                        <button 
                            type="reset" 
                            class="btn-mobile px-4 py-2 bg-gray-100 text-text-secondary rounded-lg hover:bg-gray-200 transition-colors flex items-center justify-center space-x-2"
                        >
                            <i class="fa fa-refresh"></i>
                            <span>清空</span>
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- 操作提示 -->
            <div class="mt-6 bg-white rounded-xl shadow-card p-4 animate-slide-up text-sm" style="animation-delay: 0.3s;">
                <div class="flex items-start">
                    <i class="fa fa-info-circle text-primary mt-0.5 mr-2"></i>
                    <div>
                        <p class="text-text-secondary">克隆过程将在后台运行，完成后会自动保存到本地库。仅支持抖音链接。</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- 回到顶部按钮 -->
    <button id="backToTop" class="fixed bottom-6 right-6 w-12 h-12 rounded-full bg-primary text-white shadow-lg flex items-center justify-center opacity-0 invisible transition-all z-50">
        <i class="fa fa-arrow-up"></i>
    </button>

    <!-- JavaScript -->
    <script>
        // 导航栏滚动效果
        window.addEventListener('scroll', function() {
            const header = document.getElementById('header');
            const backToTop = document.getElementById('backToTop');
            
            // 导航栏样式变化
            if (window.scrollY > 50) {
                header.classList.add('shadow-md', 'py-2');
                header.classList.remove('py-3', 'shadow-sm');
            } else {
                header.classList.remove('shadow-md', 'py-2');
                header.classList.add('py-3', 'shadow-sm');
            }
            
            // 回到顶部按钮显示/隐藏
            if (window.scrollY > 300) {
                backToTop.classList.remove('opacity-0', 'invisible');
                backToTop.classList.add('opacity-100', 'visible');
            } else {
                backToTop.classList.add('opacity-0', 'invisible');
                backToTop.classList.remove('opacity-100', 'visible');
            }
        });
        
        // 回到顶部功能
        document.getElementById('backToTop').addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });

        // 移动端输入框自动调整高度
        const textarea = document.getElementById('content');
        if (textarea && window.innerWidth < 640) {
            textarea.addEventListener('input', function() {
                this.style.height = 'auto';
                // 限制最大高度
                if (this.scrollHeight > 300) {
                    this.style.height = '300px';
                    this.style.overflowY = 'auto';
                } else {
                    this.style.height = this.scrollHeight + 'px';
                    this.style.overflowY = 'hidden';
                }
            });
        }
    </script>
</body>
</html>