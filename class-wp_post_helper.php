<?php
/***
 * en : https://gist.github.com/4084471
 * ja : https://gist.github.com/4078027
 **/

if (defined('ABSPATH')) :

require_once(ABSPATH . 'wp-admin/includes/image.php');
require_once(ABSPATH . 'wp-admin/includes/post.php');

class wp_post_helper {
	public $post;

	private $postid = false;
	private $attachment_id = array();

	private $tags   = array();	
	private $medias = array();
	private $metas  = array();
	private $fields = array();

	function __construct($args = array()){
		$this->init();
		if (is_array($args) && count($args) > 0)
			$this->set($args);
	}

	// Get PostID
	public function postid(){
		return $this->postid;
	}

	// Get Attachment ID
	public function attachment_id(){
		return $this->attachment_id;
	}

	// Init Post Data
	public function init(){
		$this->post = get_default_post_to_edit();
		$this->post->post_category = null;
		$this->attachment_id = array();
		$this->tags   = array();
		$this->medias = array();
		$this->metas  = array();
		$this->fields = array();
	}

	// Set Post Data
	public function set($args) {
		if (!is_array($args))
			return false;

		$post = $this->post;
		foreach ($post as $key => &$val) {
			if (isset($args[$key])) {
				$val = $args[$key];
			}
		}
		$this->post = $post;

		if (isset($args['post_tags'])) {
			$this->add_tags(
				is_array($args['post_tags'])
				? $args['post_tags']
				: explode(',', $args['post_tags'])
				);
		}
	}

	// Add Post
	public function insert(){
		if (!isset($this->post))
			return false;

		$postid = 
			!$this->postid
			? wp_insert_post($this->post)
			: $this->postid;

		if ($postid && !is_wp_error($postid)) {
			$this->postid   = $postid;
			$this->post->ID = $postid;

			// add Tags
			if (count($this->tags) > 0)
				$this->add_tags($this->tags);
			$this->tags = array();
			
			// add medias
			foreach ($this->medias as $key => $val) {
				$this->add_media($key, $val[0], $val[1], $val[2], $val[3]);
			}
			$this->medias = array();

			// add Custom Fields
			foreach ($this->metas as $key => $val) {
				if (is_array($val))
					$this->add_meta($key, $val[0], isset($val[1]) ? $val[1] : true);
				else
					$this->add_meta($key, $val);
			}
			$this->metas = array();

			// add ACF Fields
			foreach ($this->fields as $key => $val) {
				$this->add_field($key, $val);
			}
			$this->fields = array();

			return $postid;

		} else {
			$this->postid   = false;
			$this->post->ID = 0;
			return false;
		}
	}

	// Add Tag
	public function add_tags($tags = array()){
		$tags = is_array($tags) ? $tags : explode(',', $tags);
		foreach ($tags as $tag) {
			if (!empty($tag) && !array_search($tag, $this->tags))
				$this->tags[] = $tag;
		}
		unset($tags);

		if ($this->postid) {
			$tags = implode(',', $this->tags);
			$this->tags = array();
			return wp_add_post_tags($this->postid, $tags);
		}
	}

	// Add Media
	public function add_media($filename, $title = null, $content = null, $excerpt = null, $thumbnail = false){
		if (!$this->postid) {
			$this->medias[$filename] = array(
				$title,
				$content,
				$excerpt,
				$thumbnail,
				);
			return;
		}
	
		if ( $filename && file_exists($filename) ) {
			$mime_type = '';
			$wp_filetype = wp_check_filetype(basename($filename), null);
			if (isset($wp_filetype['type']) && $wp_filetype['type'])
				$mime_type = $wp_filetype['type'];
			unset($wp_filetype);
			
			$title = isset($title) ? $title : preg_replace('/\.[^.]+$/', '', basename($filename));
			$content = isset($content) ? $content : $title;
			$excerpt = isset($excerpt) ? $excerpt : $content;
			$attachment = array(
				'post_mime_type' => $mime_type ,
				'post_parent'    => $this->postid ,
				'post_author'    => $this->post->post_author ,
				'post_title'     => $title ,
				'post_content'   => $content ,
				'post_excerpt'   => $excerpt ,
				'post_status'    => 'inherit',
			);
			if (isset($this->post->post_name) && $this->post->post_name)
				$attachment['post_name'] = $this->post->post_name;
			$attachment_id = wp_insert_attachment($attachment, $filename, $this->postid);
			unset($attachment);

			if (!is_wp_error($attachment_id)) {
				$this->attachment_id[] = $attachment_id;
				$attachment_data = wp_generate_attachment_metadata($attachment_id, $filename);
				wp_update_attachment_metadata($attachment_id,  $attachment_data);
				unset($attachment_data);
				if ($thumbnail)
					set_post_thumbnail($this->postid, $attachment_id);
				return $attachment_id;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	// Add Custom Field
	public function add_meta($metakey, $val, $unique = true){
		if (!$this->postid) {
			$this->metas[$metakey] = array($val, $unique);
			return;
		}

		return
			($this->postid && $val)
			? add_post_meta($this->postid, $metakey, $val, $unique)
			: false;
	}

	// Add Advanced Custom Field
	public function add_field($field_key, $val){
		if (!$this->postid) {
			$this->fields[$field_key] = $val;
			return;
		}

		return
			($this->postid && $val)
			? update_field($field_key, $val, $this->postid)
			: false;
	}
}

function remote_get_file($url = null, $file_dir = '') {
	if (!$url)
		return false;

	if (empty($file_dir)) {
		 $upload_dir = wp_upload_dir();
		 $file_dir = isset($upload_dir['path']) ? $upload_dir['path'] : '';
	}
	$file_dir = trailingslashit($file_dir);

	// make directory
	if (!file_exists($file_dir)) {
		$dirs = explode('/', $file_dir);
		$subdir = '/';
		foreach ($dirs as $dir) {
			if (!empty($dir)) {
				$subdir .= $dir . '/';
				if (!file_exists($subdir)) {
					mkdir($subdir);
				}
			}
		}
	}

	// remote get!
	$photo = $file_dir . basename($url);
	if ( !file_exists($photo) ) {
		$response = wp_remote_get($url);
		if ( !is_wp_error($response) && $response["response"]["code"] === 200 ) {
			$photo_data = $response["body"];
			file_put_contents($photo, $photo_data);
			unset($photo_data);
		} else {
			$photo = false;
		}
		unset($response);
	}
	return file_exists($photo) ? $photo : false;
}

endif;
