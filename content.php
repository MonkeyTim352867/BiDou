<?php

// 连接数据库

$db = new SQLite3('./data.db');

// 获取用户传入的 cid
$cid = $_GET['cid'] ?? null;

// 页面配置
$pageConfig = [
    'title' => '哔抖',
    'keywords' => '视频,笔记,音乐,内容',
    'description' => '哔抖宽屏模式内容展示'
];

// 检查是否为移动端访问
$isMobile = preg_match('/(android|iphone|ipad|ipod)/i', $_SERVER['HTTP_USER_AGENT']);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="./favicon.ico" type="image/x-icon">
    <title>加载中...</title>
    <meta name="keywords" content="<?php echo htmlspecialchars($pageConfig['keywords']); ?>">
    <meta name="description" content="<?php echo $cid && $content ? htmlspecialchars(mb_substr(strip_tags($content['title']), 0, 100, 'UTF-8')) : htmlspecialchars($pageConfig['description']); ?>">
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
    <main class="container mx-auto px-4 pt-24 pb-16 flex flex-col md:flex-row gap-6 justify-center">
        <!-- 左侧内容区 -->
        <div class="w-full md:w-2/3 animate-fade-in">
            <?php if ($cid) { ?>
                <?php
                // 查询内容信息，使用参数化查询防止 SQL 注入
                $contentQuery = $db->prepare("SELECT * FROM contents WHERE cid = :cid");
                $contentQuery->bindParam(':cid', $cid, SQLITE3_TEXT);
                $contentResult = $contentQuery->execute();
                $content = $contentResult->fetchArray(SQLITE3_ASSOC);

                if ($content) {
                    // 查询作者信息，使用参数化查询
                    $authorUid = $content['author'];
                    $authorQuery = $db->prepare("SELECT user_name, avatar FROM users WHERE uid = :uid");
                    $authorQuery->bindParam(':uid', $authorUid, SQLITE3_TEXT);
                    $authorResult = $authorQuery->execute();
                    $author = $authorResult->fetchArray(SQLITE3_ASSOC);

                    // 查询音乐信息，使用参数化查询
                    $musicMid = $content['music'];
                    $musicQuery = $db->prepare("SELECT title, author as music_author, cover, content as music_content FROM musics WHERE mid = :mid");
                    $musicQuery->bindParam(':mid', $musicMid, SQLITE3_TEXT);
                    $musicResult = $musicQuery->execute();
                    $music = $musicResult->fetchArray(SQLITE3_ASSOC);
                ?>
                    <!-- 内容标题 -->
                    <div class="mb-6 animate-slide-up" style="animation-delay: 0.1s;">
                        <h1 class="text-[clamp(1.5rem,3vw,2.5rem)] font-bold text-text-primary leading-tight mb-2">
                            <?php echo str_replace(["\r\n", "\n", "\r"], '<br>', htmlspecialchars($content['title'])); ?>
                        </h1>
                        <div class="flex flex-wrap items-center gap-4 text-text-tertiary text-sm">
                            <span class="flex items-center"><i class="fa fa-clock-o mr-1"></i> <?php echo date('Y-m-d H:i', $content['publish_time']); ?></span>
                            <?php
                            $tags = json_decode($content['tag'] ?? '[]', true);
                            foreach ($tags as $tag) {
                                if (trim($tag)) {
                                    echo '<span class="px-2 py-0.5 bg-gray-100 rounded text-xs">'.htmlspecialchars(trim($tag)).'</span>';
                                }
                            }
                            ?>
                        </div>
                    </div>
                    
                    <!-- 作者信息 -->
                    <div class="flex items-center justify-between mb-6 animate-slide-up" style="animation-delay: 0.2s;">
                        <a href="./user.php?uid=<?php echo htmlspecialchars($content['author']); ?>">
                        <div class="flex items-center space-x-3">
                            <div class="w-12 h-12 rounded-full overflow-hidden border-2 border-transparent hover:border-primary transition-all">
                                <img src="<?php echo htmlspecialchars($author['avatar']); ?>" alt="<?php echo htmlspecialchars($author['user_name']); ?>的头像" class="w-full h-full object-cover" />
                            </div>
                            <div>
                                <h3 class="font-bold text-lg"><?php echo htmlspecialchars($author['user_name']); ?></h3>
                            </div>
                        </div>
                        </a>
                        <button class="px-4 py-2 bg-primary text-white rounded-full flex items-center space-x-2 hover:bg-primary/90 transition-colors shadow-sm">
                            <i class="fa fa-plus"></i>
                            <span>关注</span>
                        </button>
                    </div>
                    
                    <!-- 内容展示区 -->
                    <div class="bg-white rounded-xl shadow-card p-4 mb-6 animate-slide-up" style="animation-delay: 0.3s;">
                        <?php if ($content['type'] === 'video') { ?>
                            <div class="video-container rounded-lg overflow-hidden relative group">
                                <video controls class="w-full h-full object-cover bg-black" poster="<?php echo htmlspecialchars($content['cover'] ?? ''); ?>">
                                    <source src="<?php echo htmlspecialchars($content['content']); ?>" type="video/mp4">
                                    您的浏览器不支持视频播放。
                                </video>
                                <!-- 视频悬停控制层 -->
                                <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex items-end p-4 pointer-events-none">
                                    <div class="w-full text-white">
                                        <div class="flex justify-between items-center">
                                            <div class="text-sm font-medium">
                                                <?php echo htmlspecialchars($content['title']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php if (!empty($content['music'])): ?>
                            <!-- 音频信息 -->
                            <div class="mt-6 p-4 bg-gray-50 rounded-lg flex items-center space-x-4 shadow-sm">
                                <div class="w-12 h-12 rounded-full overflow-hidden bg-gray-200 flex items-center justify-center">
                                    <img src="<?php echo htmlspecialchars($music['cover']); ?>" alt="<?php echo htmlspecialchars($music['title']); ?>封面" class="w-full h-full object-cover" />
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-medium"><?php echo htmlspecialchars($music['title']); ?></h4>
                                    <p class="text-text-tertiary text-sm"><?php echo htmlspecialchars($music['music_author']); ?></p>
                                </div>
                            </div>
                            <?php endif ?>
                        <?php } elseif ($content['type'] === 'note') { ?>
                            <div class="space-y-4">
                                <?php
                                $noteContent = json_decode($content['content'], true);
                                foreach ($noteContent as $item) {
                                    if (preg_match('/\.(jpg|jpeg|png|webp|gif|bmp)$/i', $item)) {
                                        echo '<div class="rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-shadow">';
                                        echo '<img src="'.htmlspecialchars($item).'" alt="笔记图片" class="w-full h-auto object-cover" loading="lazy">';
                                        echo '</div>';
                                    } elseif (preg_match('/\.(mp4|mpeg|mov|mkv|webm)$/i', $item)) {
                                        echo '<div class="video-container rounded-lg overflow-hidden shadow-sm">';
                                        echo '<video controls class="w-full h-full object-cover bg-black">';
                                        echo '<source src="'.htmlspecialchars($item).'" type="video/mp4">';
                                        echo '您的浏览器不支持视频播放。';
                                        echo '</video>';
                                        echo '</div>';
                                    } elseif (preg_match('/\.(mp3|wav|ogg|flac)$/i', $item)) {
                                        echo '<div class="bg-gray-50 rounded-lg p-4 flex items-center space-x-3 shadow-sm">';
                                        echo '<i class="fa fa-music text-primary text-xl"></i>';
                                        echo '<audio controls class="w-full">';
                                        echo '<source src="'.htmlspecialchars($item).'" type="audio/mpeg">';
                                        echo '您的浏览器不支持音频播放。';
                                        echo '</audio>';
                                        echo '</div>';
                                    } else {
                                        // 处理文本内容
                                        echo '<p class="text-text-primary leading-relaxed">'.htmlspecialchars($item).'</p>';
                                    }
                                }
                                ?>
                                
                                <!-- 背景音乐 -->
                                <div class="mt-6 p-4 bg-gray-50 rounded-lg flex items-center space-x-4 shadow-sm">
                                    <div class="w-12 h-12 rounded-full overflow-hidden bg-gray-200 flex items-center justify-center">
                                        <img src="<?php echo htmlspecialchars($music['cover']); ?>" alt="<?php echo htmlspecialchars($music['title']); ?>封面" class="w-full h-full object-cover" />
                                    </div>
                                    <div class="flex-1">
                                        <h4 class="font-medium"><?php echo htmlspecialchars($music['title']); ?></h4>
                                        <p class="text-text-tertiary text-sm"><?php echo htmlspecialchars($music['music_author']); ?></p>
                                    </div>
                                    <audio controls loop class="w-40" id="bgm">
                                        <source src="<?php echo htmlspecialchars($music['music_content']); ?>" type="audio/mpeg">
                                        您的浏览器不支持音频播放。
                                    </audio>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                    
                    <!-- 内容标签和分享 -->
                    <div class="flex flex-wrap justify-between items-center mb-6 animate-slide-up" style="animation-delay: 0.4s;">
                        <div class="flex flex-wrap gap-2">
                            <?php
                            $tags = json_decode($content['tag'] ?? '[]', true);
                            foreach ($tags as $tag) {
                                if (trim($tag)) {
                                    echo '<a href="./search.php?q=%23' . htmlspecialchars(trim($tag)) . '" class="px-3 py-1 bg-gray-100 rounded-full text-sm text-text-secondary hover:bg-gray-200 transition-colors">#' . htmlspecialchars(trim($tag)) . '</a>';
                                }
                            }
                            ?>
                        </div>
                        <div class="flex items-center space-x-4 mt-2 sm:mt-0">
                            <span class="text-text-tertiary text-sm mr-2">分享：</span>
                            <button id="share_button" class="w-8 h-8 flex items-center justify-center rounded-full bg-gray-100 text-text-secondary hover:bg-primary hover:text-white transition-colors">
                                <i class="fa fa-link"></i>
                            </button>
                            <?php if ($content['source'] === "douyin"): ?>
                                <a href="<?php echo htmlspecialchars($content['url']) ?>">
                                    <button class="w-8 h-8 flex items-center justify-center rounded-full bg-gray-100 text-text-secondary hover:bg-primary hover:text-white transition-colors">
                                        <img src="./ui/douyin.svg" class="w-4 h-4">
                                    </button>
                                </a>
                            <?php endif ?>
                        </div>
                    </div>
                    
                    <!-- 互动按钮 -->
                    <div class="flex flex-wrap justify-between items-center gap-4 mb-8 animate-slide-up" style="animation-delay: 0.5s;">
                        <div class="flex space-x-2 sm:space-x-4">
                            <button class="flex flex-col items-center justify-center px-4 py-3 rounded-xl hover:bg-gray-100 transition-colors group">
                                <i class="fa fa-heart text-xl mb-1 group-hover:text-primary transition-colors"></i>
                                <span class="text-sm">点赞 <?php echo $content['like']; ?></span>
                            </button>
                            <button class="flex flex-col items-center justify-center px-4 py-3 rounded-xl hover:bg-gray-100 transition-colors group">
                                <i class="fa fa-star text-xl mb-1 group-hover:text-primary transition-colors"></i>
                                <span class="text-sm">收藏 <?php echo $content['collect']; ?></span>
                            </button>
                            <button class="flex flex-col items-center justify-center px-4 py-3 rounded-xl hover:bg-gray-100 transition-colors group">
                                <i class="fa fa-share-alt text-xl mb-1 group-hover:text-primary transition-colors"></i>
                                <span class="text-sm">分享 <?php echo $content['share']; ?></span>
                            </button>
                        </div>
                    </div>
                <?php } else { ?>
                    <div class="bg-white rounded-xl shadow-card p-8 text-center">
                        <div class="text-5xl text-gray-300 mb-4">
                            <i class="fa fa-frown-o"></i>
                        </div>
                        <h3 class="text-xl font-bold mb-2">未找到该内容</h3>
                        <p class="text-text-secondary mb-6">你要查看的内容可能已被删除或不存在</p>
                        <a href="./" class="px-5 py-2 bg-primary text-white rounded-full hover:bg-primary/90 transition-colors shadow-sm inline-block">
                            返回首页
                        </a>
                    </div>
                <?php } ?>
            <?php } else { ?>
                <div class="bg-white rounded-xl shadow-card p-8 text-center">
                    <div class="text-5xl text-gray-300 mb-4">
                        <i class="fa fa-exclamation-circle"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-2">请提供有效的 cid</h3>
                    <p class="text-text-secondary mb-6">没有指定内容ID，请从正确的链接访问</p>
                    <a href="./" class="px-5 py-2 bg-primary text-white rounded-full hover:bg-primary/90 transition-colors shadow-sm inline-block">
                        返回首页
                    </a>
                </div>
            <?php } ?>
        </div>
    </main>

    <!-- 底部信息 -->
    <footer class="hidden">
    </footer>

    <!-- 回到顶部按钮 -->
    <button id="backToTop" class="fixed bottom-6 right-6 w-12 h-12 rounded-full bg-primary text-white shadow-lg flex items-center justify-center opacity-0 invisible transition-all z-50">
        <i class="fa fa-arrow-up"></i>
    </button>

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


        const fullUrl = window.location.href;

        function copyToClipboard(text) {
        // 创建一个临时文本区域元素
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
  
        // 将文本区域添加到文档中
        document.body.appendChild(textarea);
  
        // 选中并复制文本
        textarea.select();
        document.execCommand('copy');
  
        // 移除临时文本区域
        document.body.removeChild(textarea);
  
        //showMessage('链接已复制到剪贴板');
        }

        const share_button = document.getElementById('share_button');
        share_button.addEventListener('click',function() {
            copyToClipboard(fullUrl)
        });

        document.addEventListener('DOMContentLoaded', function() {
            document.title = "<?php echo $cid ? str_replace(["\r\n", "\n", "\r"], ' ', htmlspecialchars($content['title'])) . ' - ' : ''; ?><?php echo htmlspecialchars($pageConfig['title']); ?>";
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
        
        // 视频自动播放控制
        document.addEventListener('DOMContentLoaded', function() {
            // 为视频添加自动播放控制
            const videos = document.querySelectorAll('video');
            videos.forEach(video => {
                // 当视频进入视口时自动播放，离开时暂停
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            video.play().catch(err => {
                                console.log('自动播放被浏览器阻止:', err);
                            });
                        } else {
                            video.pause();
                        }
                    });
                }, { threshold: 0.5 });
                
                observer.observe(video);
            });
        });
    </script>
</body>
</html>