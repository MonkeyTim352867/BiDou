<?php
try {
    // 连接到 SQLite 数据库
    $conn = new PDO('sqlite:data.db');
    // 设置 PDO 错误模式为异常
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("数据库连接失败: " . $e->getMessage());
}

// 获取当前加载的偏移量，默认为 0
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
// 每次加载 12 条内容
$limit = 12;

// 随机查询 12 条内容
$sql = "SELECT c.*, u.user_name, u.avatar FROM contents c 
        JOIN users u ON c.author = u.uid 
        ORDER BY RANDOM() 
        LIMIT :limit OFFSET :offset";
$stmt = $conn->prepare($sql);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$recommended_results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 关闭数据库连接（PDO 会在脚本结束时自动关闭连接）
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="./favicon.ico" type="image/x-icon">
    <title>推荐页 - 哔抖</title>
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

        /* 链接样式 */
        a {
            text-decoration: none;
            color: #222222;
        }

        /* 空心爱心 */
       .fa-heart-o {
            color: black;
        }

        /* 头像圆形样式 */
       .avatar-round {
            border-radius: 50%;
        }

        /* 点赞量叠层显示在封面右下角 */
       .like-overlay {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background-color: rgba(230, 230, 230, 0.4);
            color: black;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 14px;
        }

       .author-overlay {
            position: absolute;
            bottom: 10px;
            left: 10px;
        }

        .title-wrap-ellipsis {
            word-wrap: break-word;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
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
                    <a href="./" class="text-primary font-medium hover:text-primary/80 transition-colors">首页</a>
                    <a href="javascript:void(0)" class="text-text-secondary hover:text-primary transition-colors">内容</a>
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
        <div class="w-full md:w-full animate-fade-in">
            <h1 class="text-[clamp(1.5rem,3vw,2.5rem)] font-bold text-text-primary leading-tight mb-2">推荐内容</h1>
            <?php if (!empty($recommended_results)): ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4" id = "main-content">
                    <?php foreach ($recommended_results as $result): ?>
                        <div class="bg-white rounded-xl shadow-card p-4 relative">
                            <a href="./content.php?cid=<?php echo htmlspecialchars($result['cid']); ?>">
                                <img src="<?php echo htmlspecialchars($result['cover']); ?>" alt="<?php echo htmlspecialchars($result['title']); ?> 封面" class="w-full h-64 object-cover rounded-lg mb-2">
                            </a>
                            <span class="like-overlay">
                                <i class="fa fa-heart-o mr-1"></i> <?php echo htmlspecialchars($result['like']); ?>
                            </span>
                            <div class="flex flex-wrap gap-1">
                                <?php
                                $tags = json_decode($result['tag'] ?? '[]', true);
                                foreach ($tags as $tag) {
                                    if (trim($tag)) {
                                        echo '<a href="./search.php?q=%23' . htmlspecialchars(trim($tag)) . '" class="px-2 py-0.5 bg-gray-100 rounded text-xs text-text-secondary hover:bg-gray-200 transition-colors">#'.htmlspecialchars(trim($tag)).'</a>';
                                    }
                                }
                                ?>
                            </div>
                            <h2 class="text-lg font-bold mb-1 title-wrap-ellipsis">
                                <a href="./content.php?cid=<?php echo htmlspecialchars($result['cid']); ?>">
                                    <?php echo htmlspecialchars($result['title']); ?>
                                </a>
                            </h2>
                            <br>
                            <div class="flex items-center space-x-2 mb-1 author-overlay">
                                <a href="./user.php?uid=<?php echo htmlspecialchars($result['author']); ?>">
                                    <img src="<?php echo htmlspecialchars($result['avatar']); ?>" alt="<?php echo htmlspecialchars($result['user_name']); ?> 头像" class="w-6 h-6 avatar-round">
                                </a>
                                <a href="./user.php?uid=<?php echo htmlspecialchars($result['author']); ?>" class="text-text-secondary text-sm">
                                    <?php echo htmlspecialchars($result['user_name']); ?>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="bg-white rounded-xl shadow-card p-8 text-center">
                    <div class="text-5xl text-gray-300 mb-4">
                        <i class="fa fa-frown-o"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-2">暂无推荐内容</h3>
                    <p class="text-text-secondary mb-6">请稍后再试</p>
                    <a href="./" class="px-5 py-2 bg-primary text-white rounded-full hover:bg-primary/90 transition-colors shadow-sm inline-block">
                        返回首页
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </main>
    <script>
        let currentOffset = <?php echo $offset; ?>;
        const limit = <?php echo $limit; ?>;
        const grid = document.querySelector('.grid');

        window.addEventListener('scroll', function() {
            if ((window.innerHeight + window.scrollY) >= document.body.offsetHeight) {
                loadMore();
            }
        });

        function loadMore() {
            currentOffset += limit;
            const xhr = new XMLHttpRequest();
            xhr.open('GET', `index.php?offset=${currentOffset}`, true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(xhr.responseText, 'text/html');
                    const newItems = doc.querySelectorAll('.grid > div');
                    console.log(grid);
                    newItems.forEach(item => {
                        //const con = item.innerHTML
                        //console.log(con);
                        grid.appendChild(item);
                    });
                }
            };
            xhr.send();
        }

        // 搜索函数
        function performSearch() {
            const searchInput = document.getElementById('search-input');
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

        // 页面加载完成后绑定事件
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('search-input');
            const searchButton = document.getElementById('search-button');

            // 绑定按钮点击事件
            searchButton.addEventListener('click', performSearch);

            // 绑定输入框回车键事件
            searchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    performSearch();
                }
            });
        });
    </script>
</body>
</html>