<?php
$posts = get_posts(array('post_type'=>'product','numberposts'=>-1));
$out = array();
foreach($posts as $p){
  $id = $p->ID;
  $meta = get_post_meta($id);
  $gallery = get_post_meta($id, '_product_image_gallery', true);
  $gallery_arr = $gallery ? array_filter(array_map('trim', explode(',', $gallery))) : array();
  $thumb = get_post_meta($id, '_thumbnail_id', true);
  $price = '';
  if ( isset($meta['_price'][0]) ) $price = $meta['_price'][0];
  $badge = isset($meta['beslock_badge'][0]) ? $meta['beslock_badge'][0] : '';
  $out[] = array('ID'=>$id,'slug'=>$p->post_name,'title'=>$p->post_title,'excerpt'=>$p->post_excerpt,'price'=>$price,'badge'=>$badge,'meta'=>$meta,'gallery_ids'=>$gallery_arr,'thumbnail_id'=>$thumb);
}
echo json_encode($out, JSON_UNESCAPED_UNICODE);
