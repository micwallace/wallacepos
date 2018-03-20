<?php
/**
 * DOMPDF - PHP5 HTML to PDF renderer
 *
 * File: $RCSfile: page_frame_reflower.cls.php,v $
 * Created on: 2004-06-16
 *
 * Copyright (c) 2004 - Benj Carson <benjcarson@digitaljunkies.ca>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this library in the file LICENSE.LGPL; if not, write to the
 * Free Software Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA
 * 02111-1307 USA
 *
 * Alternatively, you may distribute this software under the terms of the
 * PHP License, version 3.0 or later.  A copy of this license should have
 * been distributed with this file in the file LICENSE.PHP .  If this is not
 * the case, you can obtain a copy at http://www.php.net/license/3_0.txt.
 *
 * The latest version of DOMPDF might be available at:
 * http://www.dompdf.com/
 *
 * @link http://www.dompdf.com/
 * @copyright 2004 Benj Carson
 * @author Benj Carson <benjcarson@digitaljunkies.ca>
 * @package dompdf

 */

/* $Id: page_frame_reflower.cls.php 357 2011-01-30 20:56:46Z fabien.menager $ */

/**
 * Reflows pages
 *
 * @access private
 * @package dompdf
 */
class Page_Frame_Reflower extends Frame_Reflower {

  /**
   * Cache of the callbacks array
   * 
   * @var array
   */
  private $_callbacks;
  
  /**
   * Cache of the canvas
   *
   * @var Canvas
   */
  private $_canvas;
  
  /**
   * This page's floating frames
   * 
   * @var array
   */
  private $_floating_frames;
  
  /**
   * true if the page has floating frales
   * Used to improve performances as it will
   * be accesses everytime
   * @var bool
   */
  private $_has_floating_frames;
  
  /**
   * The stacking context, containing all z-indexed frames
   * @var array
   */
  private $_stacking_context = array();

  function __construct(Page_Frame_Decorator $frame) { parent::__construct($frame); }
  
  /**
   * @return array
   */
  function get_floating_frames() { return $this->_floating_frames; }

  /**
   * @param $frame Frame
   * @return void
   */
  function add_frame_to_stacking_context(Frame $frame, $z_index) {
    $this->_stacking_context[$z_index][] = $frame;
  }
  
  /**
   * Add a floating frame
   * 
   * @param $child Frame
   */
  function add_floating_frame(Frame $frame) {
    $this->_floating_frames[] = $frame;
    $this->_has_floating_frames = true;
  }
  
  /**
   * Tells if the page has floating frames
   * 
   * @return bool 
   */
  function has_floating_frames() {
    return $this->_has_floating_frames;
  }
  
  //........................................................................

  function reflow(Frame_Decorator $block = null) {
    $style = $this->_frame->get_style();
    
    // Paged layout:
    // http://www.w3.org/TR/CSS21/page.html

    // Pages are only concerned with margins
    $cb = $this->_frame->get_containing_block();
    $left   = $style->length_in_pt($style->margin_left,   $cb["w"]);
    $right  = $style->length_in_pt($style->margin_right,  $cb["w"]);
    $top    = $style->length_in_pt($style->margin_top,    $cb["h"]);
    $bottom = $style->length_in_pt($style->margin_bottom, $cb["h"]);
    
    $content_x = $cb["x"] + $left;
    $content_y = $cb["y"] + $top;
    $content_width = $cb["w"] - $left - $right;
    $content_height = $cb["h"] - $top - $bottom;
    
    $fixed_children = array();
    
    $prev_child = null;
    $child = $this->_frame->get_first_child();
    $current_page = 0;
		
    while ($child) {
      // Only if it's the first page, we save the nodes with a fixed position
      if ($current_page == 0) {
        $children = $child->get_children();
        foreach ($children as $onechild) {
          if ($onechild->get_style()->position === "fixed") {
            $fixed_children[] = $onechild->deep_copy();
          }
        }
        $fixed_children = array_reverse($fixed_children);
      }
      
      $child->set_containing_block($content_x, $content_y, $content_width, $content_height);
      
      // Check for begin reflow callback
      $this->_check_callbacks("begin_page_reflow", $child);
    
      //Insert a copy of each node which have a fixed position
      if ($current_page >= 1) {
        foreach ($fixed_children as $fixed_child) {
          $child->insert_child_before($fixed_child->deep_copy(), $child->get_first_child());
        }
      }
      
      $child->reflow();
      $next_child = $child->get_next_sibling();
      
      // Check for begin render callback
      $this->_check_callbacks("begin_page_render", $child);
      
      $renderer = $this->_frame->get_renderer();

      // Render the page
      $renderer->render($child);
      
      // http://www.w3.org/TR/CSS21/visuren.html#z-index
      ksort($this->_stacking_context);
      
      foreach( $this->_stacking_context as $_z_index => $_frames ) {
        foreach ( $_frames as $_frame ) {
          $renderer->render($_frame);
        }
      }
      
      $this->_stacking_context = array();
      
      // Check for end render callback
      $this->_check_callbacks("end_page_render", $child);
      
      if ( $next_child ) {
        $this->_frame->next_page();
      }

      // Wait to dispose of all frames on the previous page
      // so callback will have access to them
      if ( $prev_child ) {
        $prev_child->dispose(true);
      }
      $prev_child = $child;
      $child = $next_child;
      $current_page++;
    }

    // Dispose of previous page if it still exists
    if ( $prev_child ) {
      $prev_child->dispose(true);
    }
  }  
  
  //........................................................................
  
  /**
   * Check for callbacks that need to be performed when a given event
   * gets triggered on a page
   *
   * @param string $event the type of event
   * @param Frame $frame the frame that event is triggered on
   */
  protected function _check_callbacks($event, $frame) {
    if (!isset($this->_callbacks)) {
      $dompdf = $this->_frame->get_dompdf();
      $this->_callbacks = $dompdf->get_callbacks();
      $this->_canvas = $dompdf->get_canvas();
    }
    
    if (is_array($this->_callbacks) && isset($this->_callbacks[$event])) {
      $info = array(0 => $this->_canvas, "canvas" => $this->_canvas,
                    1 => $frame, "frame" => $frame);
      $fs = $this->_callbacks[$event];
      foreach ($fs as $f) {
        if (is_callable($f)) {
          if (is_array($f)) {
            $f[0]->$f[1]($info);
          } else {
            $f($info);
          }
        }
      }
    }
  }  
}
