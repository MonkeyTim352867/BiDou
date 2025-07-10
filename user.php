<?php
// 连接SQLite数据库
$db = new SQLite3('./data.db');
if (!$db) {
    die("数据库连接失败: " . $db->lastErrorMsg());
}

// 获取传入的uid
$uid = isset($_GET['uid']) ? $_GET['uid'] : '';
if (empty($uid)) {
    die("请提供用户ID (uid)");
}

// 查询用户信息
$userStmt = $db->prepare("SELECT * FROM users WHERE uid = :uid");
$userStmt->bindValue(':uid', $uid, SQLITE3_TEXT);
$userResult = $userStmt->execute();
$user = $userResult->fetchArray(SQLITE3_ASSOC);

if (!$user) {
    die("未找到该用户");
}

// 查询用户发布的内容
$contentStmt = $db->prepare("SELECT * FROM contents WHERE author = :uid ORDER BY publish_time DESC");
$contentStmt->bindValue(':uid', $uid, SQLITE3_TEXT);
$contentResult = $contentStmt->execute();

// 处理列表字段（SQLite中列表以逗号分隔存储）
$liked = !empty($user['liked']) ? explode(',', $user['liked']) : [];
$collected = !empty($user['collected']) ? explode(',', $user['collected']) : [];
$follow = !empty($user['follow']) ? explode(',', $user['follow']) : [];
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="./favicon.ico" type="image/x-icon">
    <title><?php echo htmlspecialchars($user['user_name']); ?>的主页</title>
    <meta name="keywords" content="视频,笔记,音乐,内容">
    <meta name="description" content="哔抖宽屏模式用户页展示">
    <!-- Tailwind CSS -->
    <script src="./lib/3.4.16"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="./lib/font-awesome.min.css">
    <!-- 配置Tailwind -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#84a2fe', // 新主题色
                        secondary: '#23ADE5', // B站蓝色
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
            .text-shadow {
                text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            }
            .animate-fade-in {
                animation: fadeIn 0.5s ease-in-out;
            }
            .animate-slide-up {
                animation: slideUp 0.6s ease-out;
            }
            .video-container {
                aspect-ratio: 16/9;
            }
            .truncate-2-lines {
                display: -webkit-box;
                -webkit-box-orient: vertical;
                -webkit-line-clamp: 2;
                overflow: hidden;
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
    </style>
</head>
<body class="bg-gray-50 font-sans text-text-primary">
    <!-- 顶部导航栏 -->
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
                    <a href="javascript:void(0)" class="text-primary font-medium hover:text-primary/80 transition-colors">内容</a>
                    <a href="./clone.php" class="text-text-secondary hover:text-primary transition-colors">克隆</a>
                </nav>
            </div>
            
            <!-- 搜索栏 -->
            <div class="hidden md:flex items-center flex-1 max-w-md mx-8">
                <div class="relative w-full">
                    <input 
                        type="text" 
                        id="search-input" 
                        placeholder="搜索视频、用户..." 
                        class="w-full py-2 px-4 pr-10 rounded-full bg-gray-100 focus:bg-white border border-gray-200 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary transition-all duration-300" 
                    />
                    <button 
                        id="search-button" 
                        class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-primary transition-colors"
                    >
                        <i class="fa fa-search"></i>
                    </button>
                </div>
            </div>
            
            <!-- 用户操作区 -->
            <div class="flex items-center space-x-4">
                <button class="hidden sm:block text-text-secondary hover:text-primary transition-colors">
                    <i class="fa fa-message text-xl"></i>
                </button>
                <button class="hidden sm:block text-text-secondary hover:text-primary transition-colors">
                    <i class="fa fa-bell text-xl"></i>
                </button>
                <button class="hidden sm:flex items-center space-x-1 text-text-secondary hover:text-primary transition-colors">
                    <i class="fa fa-upload text-xl"></i>
                    <span>投稿</span>
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
    <main class="container mx-auto px-4 pt-24 pb-16">
        <!-- 用户信息区 -->
        <div class="bg-white rounded-xl shadow-card p-8 mb-8 animate-fade-in">
            <div class="relative">
                <?php if (!empty($user['background'])): ?>
                    <img src="<?php echo htmlspecialchars($user['background']); ?>" class="w-full h-64 object-cover rounded-t-xl" alt="背景图">
                <?php endif; ?>
                <?php if (empty($user['background'])): ?>
                    <img src="./default/background.png" class="w-full h-64 object-cover rounded-t-xl" alt="背景图">
                <?php endif; ?>
                <img src="<?php echo htmlspecialchars($user['avatar']); ?>" class="absolute bottom-0 left-8 -mb-8 w-24 h-24 rounded-full border-4 border-white" alt="头像">
            </div>
            <div class="mt-12 ml-32">
                <h1 class="text-3xl font-bold text-text-primary mb-2"><?php echo htmlspecialchars($user['user_name']); ?></h1>
                <p class="text-text-secondary mb-4"><?php echo htmlspecialchars($user['introduction'] ?? '暂无介绍'); ?></p>
                <div class="flex flex-wrap gap-4 text-text-tertiary text-sm">
                    <span>ID: <?php echo htmlspecialchars($user['id']); ?></span>
                    <span>关注: <?php echo count($follow); ?></span>
                    <span>粉丝: <?php echo htmlspecialchars($user['follower'] ?? 0); ?></span>
                    <span>来源: <?php echo htmlspecialchars($user['source']); ?></span>
                    <?php if (!empty($user['url'])): ?>
                        <span>原链接: <a href="<?php echo htmlspecialchars($user['url']); ?>" target="_blank" class="text-primary hover:underline">访问</a></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- 用户发布内容区 -->
        <h2 class="text-2xl font-bold text-text-primary mb-4">发布内容</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 animate-fade-in">
            <?php while ($content = $contentResult->fetchArray(SQLITE3_ASSOC)): ?>
                <a href="./content.php?cid=<?php echo htmlspecialchars($content['cid']); ?>" class="bg-white rounded-xl shadow-card overflow-hidden group">
                    <img src="<?php echo htmlspecialchars($content['cover']); ?>" class="w-full h-48 object-cover" alt="内容封面">
                    <div class="p-4">
                        <h3 class="text-lg font-bold text-text-primary mb-2 truncate-2-lines"><?php echo htmlspecialchars($content['title'] ?? '无标题'); ?></h3>
                        <p class="text-text-tertiary text-sm mb-1">点赞: <?php echo htmlspecialchars($content['like'] ?? 0); ?></p>
                        <p class="text-text-tertiary text-sm">发布时间: <?php echo date('Y-m-d H:i', $content['publish_time']); ?></p>
                    </div>
                </a>
            <?php endwhile; ?>
        </div>
    </main>

    <!-- 回到顶部按钮 -->
    <button id="backToTop" class="fixed bottom-6 right-6 w-12 h-12 rounded-full bg-primary text-white shadow-lg flex items-center justify-center opacity-0 invisible transition-all z-50">
        <i class="fa fa-arrow-up"></i>
    </button>

    <div id="message"></div>
    <!-- JavaScript -->
    <script>
        // 获取DOM元素
        const searchInput = document.getElementById('search-input');
        const searchButton = document.getElementById('search-button');

        // 搜索函数
        function performSearch() {
            const keyword = searchInput.value.trim();
            if (keyword) {
                // 跳转到搜索结果页，携带搜索参数
                window.location.href = `./search.php?q=${encodeURIComponent(keyword)}`;
            } else {
                // 处理搜索词为空的情况（可选）
                searchInput.classList.add('border-red-500');
                setTimeout(() => searchInput.classList.remove('border-red-500'), 2000);
            }
        }

        // 绑定按钮点击事件
        searchButton.addEventListener('click', performSearch);

        // 绑定输入框回车键事件
        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                performSearch();
            }
        });

        // 监听滚动事件，控制导航栏样式和回到顶部按钮
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
    </script>
</body>
</html>