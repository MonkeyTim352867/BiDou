from queue import Full
import re
import requests
from bs4 import BeautifulSoup
import json
#from urllib.parse import unquote #已弃用
from datetime import datetime
from tqdm import tqdm
import sqlite3
import time
import os

script_dir = os.path.dirname(os.path.abspath(__file__))
os.chdir(script_dir)

def get_config():
    try:
        with open("config.json",'r',encoding='utf-8') as f:    
            json_content = json.load(f)
            cookie = json_content.get("Cookie")
            print(cookie)
            return cookie
    except:
        return ""


mobile_headers = {
    'User-Agent': 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Mobile Safari/537.36 EdgA/119.0.0.0',
    'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
    'Accept-Language': 'zh-CN,zh;q=0.9,en;q=0.8',
    'Cookie': get_config()
}
ratio = "1080p"

def get_extension(mime_type):
    mime_map = {
        # 视频类型
        'video/mp4': 'mp4',
        'video/mpeg': 'mpeg',  # MPEG 视频
        'video/quicktime': 'mov',  # QuickTime 视频（苹果常用）
        'video/x-msvideo': 'avi',  # AVI 视频
        'video/x-matroska': 'mkv',  # MKV 容器格式
        'video/x-flv': 'flv',  # FLV 视频
        'video/3gpp': '3gp',  # 3GPP 视频（移动端常用）
    
        # 音频类型
        'audio/mpeg': 'mp3',
        'audio/mp4': 'm4a',
        'audio/wav': 'wav',  # WAV 音频
        'audio/ogg': 'ogg',  # OGG 音频
        'audio/flac': 'flac',  # 无损音频格式
        'audio/aac': 'aac',  # AAC 音频
        'audio/x-ms-wma': 'wma',  # Windows 媒体音频
        # 图像类型
        'image/webp': 'webp',
        'image/jpeg': 'jpg',
        'image/png': 'png',
        'image/gif': 'gif',
        'image/svg+xml': 'svg',
        'image/bmp': 'bmp',  # 位图
        'image/tiff': 'tiff',  # 标签图像文件格式
        'image/vnd.microsoft.icon': 'ico',  # 图标文件
        'image/heic': 'heic',  # 高效图像格式（苹果常用）
        'image/heif': 'heif',  # 高效图像文件格式
    }
    return mime_map.get(mime_type, '')

def get_douyin_links(input):
    share_links = re.findall(r'https://v.douyin.com/[\w-]+/', input)
    print(share_links)
    note_links = re.findall(r'https://www.douyin.com/note/\d+', input)
    video_links = re.findall(r'https://www.douyin.com/video/\d+', input)
    user_links = re.findall(r'https://www.douyin.com/user/\d+', input)

    for link in share_links:
        try:
            response = requests.get(link,allow_redirects=True)
            content_link = response.url
            print(content_link)
            temp = re.findall(r'https://www.douyin.com/video/\d+',content_link)
            video_links = video_links + temp
            temp = re.findall(r'https://www.iesdouyin.com/share/video/\d+',content_link)
            video_links = video_links + temp
            temp = re.findall(r'https://www.douyin.com/note/\d+',content_link)
            note_links = note_links + temp
            temp = re.findall(r'https://www.iesdouyin.com/share/slides/\d+',content_link)
            note_links = note_links + temp
            temp = re.findall(r'https://www.douyin.com/user/\d+', content_link)
            user_links = user_links + temp
            temp = ""
            
        except requests.RequestException as e:
            print(f"处理链接 {link} 时出错: {e}")
            return
    #print(video_links)
    return video_links,note_links,user_links

def get_info(type,id):
    #print("start")
    if type == "slides":
        api_link = "https://www.iesdouyin.com/web/api/v2/aweme/slidesinfo/?aweme_ids=[" + id + "]&request_source=200"
        response = requests.get(api_link,headers=mobile_headers)
        r = response.text
        r = json.loads(r)
        item_info = r.get("aweme_details")
        if item_info == None:
            print("已修正")
            item_info = get_info("video",id)
            return item_info
        else:
            item_info = item_info[0]
            clone_slides(item_info)
            return item_info
 
    link = "https://www.iesdouyin.com/share/video/" + id #拼接链接 type 为 slides 或 video
    response = requests.get(link,headers=mobile_headers)
    r = response.text
    #print (r)
    #寻找script
    soup = BeautifulSoup(r, 'html.parser')
    script_tags = soup.find_all('script')
    script_contents = []
    for script in script_tags:
        if script.string:
            script_contents.append(script.string.strip())

    #寻找 window._ROUTER_DATA 
    data = ""
    for script in script_contents:
        #print (script)
        if script.find("window._ROUTER_DATA") > -1:
            data = script
    
    if data == "":
        print("请更换Cookie")
        return "new-cookie"
    json_content = data.replace("window._ROUTER_DATA = ", "", 1)
    json_content = json_content.replace(r'\u002F', '/')
    #json_content = unquote(json_content) #加上会破坏链接
    #print(json_content)
    item = json.loads(json_content)
    item_info = item.get("loaderData")
    item_info = item_info.get("video_(id)/page")
    item_info = item_info.get("videoInfoRes")
    item_info = item_info.get("item_list")
    if item_info == []:
        print("无权访问")
        return
    item_info = item_info[0]
    #print(item_info)
    clone(item_info,ratio)
    return item_info

def video_download(url,filename):
    try:
        response = requests.get(url, stream=True)
        current_time = datetime.now().strftime("%Y%m%d%H%M%S%f")
        file_type = response.headers.get('Content-Type')
        print(file_type)
        file_type = get_extension(file_type)
        path = "./item/content/video/" + current_time + "-" + filename + "." + file_type
        with open(path,'wb') as f:
            for chunk in tqdm(response.iter_content(chunk_size=8192)):
                f.write(chunk)
        
        #print("视频下载完成")
        return path
    except requests.exceptions.RequestException as e:
        print(e)

def image_download(path,url):
    try: 
        response = requests.get(url,stream=True)
        current_time = datetime.now().strftime("%Y%m%d%H%M%S%f")
        file_type = response.headers.get('Content-Type')
        print(file_type)
        file_type = get_extension(file_type) 
        path = path + current_time + "." + file_type
        with open(path,'wb') as f:
            for chunk in tqdm(response.iter_content(chunk_size=8192)):
                f.write(chunk)
        return path
    except requests.exceptions.RequestException as e:
        print(e)
        
def audio_download(url):
    path = "./item/content/music/"
    try:
        response = requests.get(url,stream=True)
        current_time = datetime.now().strftime("%Y%m%d%H%M%S%f")
        file_type = response.headers.get('Content-Type')
        print(file_type)
        file_type = get_extension(file_type) 
        path = path + current_time + "." + file_type
        with open(path,'wb') as f:
            for chunk in tqdm(response.iter_content(chunk_size=8192)):
                f.write(chunk)
        return path
    except requests.exceptions.RequestException as e:
        print(e)

def clone(info,ratio):
    #视频地址
    video = info.get("video")
    cid = info.get("aweme_id")
    video_cover = video.get("cover")
    video_cover = video_cover.get("url_list")
    video_cover = video_cover[0]
    video = video.get("play_addr")
    video = video.get("uri")
    if video.find("https://") > -1:
        music_url = video
        video_url = None
    else:
        video_url = "https://www.iesdouyin.com/aweme/v1/play/?video_id=" + video + "&ratio=" + ratio + "&line=0"
        music_url = None
    

    #视频标题
    desc = info.get("desc")
    text_extra = info.get("text_extra")
    tags = []
    for tag in text_extra:
        tag = tag.get("hashtag_name")
        tags.append(tag)
    tags = json.dumps(tags)

    #乱七八糟的量
    statistics = info.get("statistics")
    liked = statistics.get("digg_count")
    collected = statistics.get("collect_count")
    shared = statistics.get("share_count")
    aweme_id = info.get("aweme_id")

    #发布时间
    publish_time = info.get("create_time")

    #作者信息
    author_info = info.get("author")
    user_name = author_info.get("nickname") #用户名
    introduction = author_info.get("signature") #用户介绍
    douyin_id = author_info.get("unique_id") #抖音号
    uid = author_info.get("sec_uid") #唯一ID
    user_link = "https://www.douyin.com/user/" + uid #用户页
    avatar = author_info.get("avatar_medium")
    avatar = avatar.get("url_list")
    avatar = avatar[0] #用户头像

    #音乐
    music = info.get("music")
    if music == None:
        mid = None
    else:
        mid = music.get("mid")
        music_title = music.get("title")
        music_author = music.get("author")
        music_cover = music.get("cover_hd")
        if music_cover is None:
            music_cover = "https://s4.music.126.net/style/web2/img/default/default_album.jpg"
        else:
            music_cover = music_cover.get("url_list")
            music_cover = music_cover[0]

    #图片
    images = info.get("images")
    if images == None:
        image_urls = ""
    else:
        image_urls = []
        for image in images:
            image_url = image.get("url_list")
            image_url = image_url[0]
            image_urls.append(image_url)

        
    uid = "douyin-" + uid
    conn = sqlite3.connect('data.db')
    print("已连接")
    cursor = conn.cursor()
    #克隆用户
    cursor.execute("SELECT * FROM users WHERE uid = ?", (uid,))
    result = cursor.fetchone()
    if result:
        print("用户已存在")
    else:
        avatar_path = image_download("./item/images/avatar/",avatar)
        cursor.execute("INSERT INTO users (uid, introduction, id, user_name, url, source, status, avatar) VALUES (?, ?, ? ,? ,? , ?, ?, ?)", (uid, introduction, douyin_id, user_name, user_link, "douyin", "public", avatar_path))
        conn.commit()
    
    #克隆音乐
    if mid is not None:
        mid = "douyin-" + mid
        cursor.execute("SELECT * FROM musics WHERE mid = ?", (mid,))
        result = cursor.fetchone()
    else:
        result = True
    
    if result:
        print("音乐已存在或本视频无音乐")
    else:
        if music_url == None:
            music_cover_path = image_download("./item/content/music_cover/",music_cover)
            cursor.execute("INSERT INTO musics (mid, title, cover, author, source) VALUES (?, ?, ? ,? ,?)", (mid, music_title, music_cover_path, music_author, "douyin"))
        else:
            music_path = audio_download(music_url)
            music_cover_path = image_download("./item/content/music_cover/",music_cover)
            cursor.execute("INSERT INTO musics (mid, content, title, cover, author, source) VALUES (?, ?, ? ,? ,?, ?)", (mid, music_path, music_title, music_cover_path, music_author, "douyin"))
    
        conn.commit()
    
    #克隆视频
    cid = "douyin-" + cid
    cursor.execute("SELECT * FROM contents WHERE cid = ?", (cid,))
    result = cursor.fetchone()
    now_time = int(time.time())
    if result:
        print("视频已存在")
    else:
        cover_path = image_download("./item/content/cover/",video_cover)
        if video_url == None:
            images_path = []
            for image_url in image_urls:
                image_path = image_download("./item/content/images/",image_url)
                images_path.append(image_path)
            images_path = json.dumps(images_path)
            video_link = "https://www.douyin.com/note/" + aweme_id
            cursor.execute("INSERT INTO contents (source, type, like, collect, create_time, publish_time, cid, content, music, author, title, tag, status, cover, url ,share) VALUES (?, ?, ? ,? ,?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", ("douyin", "note", liked, collected, now_time, publish_time, cid, images_path, mid, uid, desc, tags, "public", cover_path, video_link, shared))
        else:
            video_path = video_download(video_url,cid)
            video_link = "https://www.douyin.com/video/" + aweme_id
            cursor.execute("INSERT INTO contents (source, type, like, collect, create_time, publish_time, cid, content, music, author, title, tag, status, cover, url ,share) VALUES (?, ?, ? ,? ,?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", ("douyin", "video", liked, collected, now_time, publish_time, cid, video_path, mid, uid, desc, tags, "public", cover_path, video_link, shared))
    
        conn.commit()
    
    cursor.close()
    conn.close()
    print("完成")

def clone_slides(info):
    #视频地址
    video = info.get("video")
    cid = info.get("aweme_id")
    video_cover = video.get("cover")
    video_cover = video_cover.get("url_list")
    video_cover = video_cover[0]
    video = video.get("play_addr")
    video = video.get("uri")
    music_url = video
    contents_url = []

    #乱七八糟的量
    statistics = info.get("statistics")
    liked = statistics.get("digg_count")
    collected = statistics.get("collect_count")
    shared = statistics.get("share_count")
    aweme_id = info.get("aweme_id")

    #视频标题
    desc = info.get("desc")
    text_extra = info.get("text_extra")
    tags = []
    for tag in text_extra:
        tag = tag.get("hashtag_name")
        tags.append(tag)
    tags = json.dumps(tags)

    #发布时间
    publish_time = info.get("create_time")

    #作者信息
    author_info = info.get("author")
    user_name = author_info.get("nickname") #用户名
    introduction = author_info.get("signature") #用户介绍
    douyin_id = author_info.get("unique_id") #抖音号
    uid = author_info.get("sec_uid") #唯一ID
    user_link = "https://www.douyin.com/user/" + uid #用户页
    avatar = author_info.get("avatar_medium")
    avatar = avatar.get("url_list")
    avatar = avatar[0] #用户头像

    #音乐
    music = info.get("music")
    mid = music.get("mid")
    music_title = music.get("title")
    music_author = music.get("author")
    music_cover = music.get("cover_hd")
    if music_cover is None:
        music_cover = "https://s4.music.126.net/style/web2/img/default/default_album.jpg"
    else:
        music_cover = music_cover.get("url_list")
        music_cover = music_cover[0]

    



    uid = "douyin-" + uid
    conn = sqlite3.connect('data.db')
    print("已连接")
    cursor = conn.cursor()
    #克隆用户
    cursor.execute("SELECT * FROM users WHERE uid = ?", (uid,))
    result = cursor.fetchone()
    if result:
        print("用户已存在")
    else:
        avatar_path = image_download("./item/images/avatar/",avatar)
        cursor.execute("INSERT INTO users (uid, introduction, id, user_name, url, source, status, avatar) VALUES (?, ?, ? ,? ,? , ?, ?, ?)", (uid, introduction, douyin_id, user_name, user_link, "douyin", "public", avatar_path))
        conn.commit()
    
    #克隆音乐
    mid = "douyin-" + mid
    cursor.execute("SELECT * FROM musics WHERE mid = ?", (mid,))
    result = cursor.fetchone()
    if result:
        print("音乐已存在")
    else:
        if music_url == None:
            music_cover_path = image_download("./item/content/music_cover/",music_cover)
            cursor.execute("INSERT INTO musics (mid, title, cover, author, source) VALUES (?, ?, ? ,? ,?)", (mid, music_title, music_cover_path, music_author, "douyin"))
        else:
            music_path = audio_download(music_url)
            music_cover_path = image_download("./item/content/music_cover/",music_cover)
            cursor.execute("INSERT INTO musics (mid, content, title, cover, author, source) VALUES (?, ?, ? ,? ,?, ?)", (mid, music_path, music_title, music_cover_path, music_author, "douyin"))
    
        conn.commit()
    
    #克隆主内容
    cid = "douyin-" + cid
    cursor.execute("SELECT * FROM contents WHERE cid = ?", (cid,))
    result = cursor.fetchone()
    now_time = int(time.time())
    if result:
        print("视频已存在")
    else:
        cover_path = image_download("./item/content/cover/",video_cover)
        contents_path = []
        contents = info.get("images")
        for content in contents:
            clip_type = content.get("clip_type")
            if clip_type == 4:
                video = content.get("video")
                video = video.get("play_addr")
                video = video.get("url_list")
                video = video[0]
                content_path = video_download(video,cid)
            else:
                image = content.get("url_list")
                image = image[0]
                content_path = image_download("./item/content/images/",image)
            contents_path.append(content_path)
        contents_path = json.dumps(contents_path)
        video_link = "https://www.douyin.com/note/" + aweme_id
        cursor.execute("INSERT INTO contents (source, type, like, collect, create_time, publish_time, cid, content, music, author, title, tag, status, cover, url ,share) VALUES (?, ?, ? ,? ,?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", ("douyin", "note", liked, collected, now_time, publish_time, cid, contents_path, mid, uid, desc, tags, "public", cover_path, video_link, shared))
        conn.commit()
    
    cursor.close()
    conn.close()
    print("完成")



def start(link):
    print(link)
    video_links,note_links,user_links = get_douyin_links(link)
    content_links = video_links + note_links
    for content_link in content_links:
        id = re.findall(r'\d+',content_link)
        id = id[0]
        print(content_link)
        print(id)
        err = get_info("slides",id)
        if err == "new-cookie":
            return "new-cookie"

def read_file():
    with open("douyin_link_list.txt",'r',encoding='utf-8') as f:
        lines = f.readlines()
        if not lines:
            return None
        else:
            first_line =lines[0].strip()

    with open("douyin_link_list.txt",'w',encoding='utf-8') as f:
        f.writelines(lines[1:])
    return first_line


q = 0 
while True:
    link = read_file()
    if link is not None:
        #cookie = get_config()
        err = start(link)
        if err == "new-cookie":
            break
        q = 0
        #break
    else:
        print(".")
        q += 1
    
    time.sleep(1)
    if q == 10:
        break