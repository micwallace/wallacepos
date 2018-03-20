<?php
/**
 * DOMPDF - PHP5 HTML to PDF renderer
 *
 * File: $RCSfile: block_frame_reflower.cls.php,v $
 * Created on: 2004-06-17
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

/* $Id: block_frame_reflower.cls.php 358 2011-01-30 22:22:47Z fabien.menager $ */

/**
 * Reflows block frames
 *
 * @access private
 * @package dompdf
 */
class Block_Frame_Reflower extends Frame_Reflower {
  // Minimum line width to justify, as fraction of available width
  const MIN_JUSTIFY_WIDTH = 0.80;

  /**
   * @var Block_Frame_Decorator
   */
  protected $_frame;
  
  function __construct(Block_Frame_Decorator $frame) { parent::__construct($frame); }

  /**
   *  Calculate the ideal used value for the width property as per:
   *  http://www.w3.org/TR/CSS21/visudet.html#Computing_widths_and_margins
   *  
   *  @param float $width
   *  @return array
   */
  protected function _calculate_width($width) {
    $style = $this->_frame->get_style();
    $w = $this->_frame->get_containing_block("w");

    if( $style->position === "fixed" )
      $w = $this->_frame->get_parent()->get_containing_block("w");

    $rm = $style->length_in_pt($style->margin_right, $w);
    $lm = $style->length_in_pt($style->margin_left, $w);

    $left = $style->length_in_pt($style->left, $w);
    $right = $style->length_in_pt($style->right, $w);
    
    // Handle 'auto' values
    $dims = array($style->border_left_width,
                  $style->border_right_width,
                  $style->padding_left,
                  $style->padding_right,
                  $width !== "auto" ? $width : 0,
                  $rm !== "auto" ? $rm : 0,
                  $lm !== "auto" ? $lm : 0);

    // absolutely positioned boxes take the 'left' and 'right' properties into account
    if ( $style->position === "absolute" || $style->position === "fixed" ) {
      $absolute = true;
      $dims[] = $left !== "auto" ? $left : 0;
      $dims[] = $right !== "auto" ? $right : 0;
    } else {
      $absolute = false;
    }

    $sum = $style->length_in_pt($dims, $w);

    // Compare to the containing block
    $diff = $w - $sum;

    if ( $diff > 0 ) {

      if ( $absolute ) {

        // resolve auto properties: see
        // http://www.w3.org/TR/CSS21/visudet.html#abs-non-replaced-width

        if ( $width === "auto" && $left === "auto" && $right === "auto" ) {

          if ( $lm === "auto" )
            $lm = 0;
          if ( $rm === "auto" )
            $rm = 0;

          // Technically, the width should be "shrink-to-fit" i.e. based on the
          // preferred width of the content...  a little too costly here as a
          // special case.  Just get the width to take up the slack:
          $left = 0;
          $right = 0;
          $width = $diff;

        } else if ( $width === "auto" ) {

          if ( $lm === "auto" )
            $lm = 0;
          if ( $rm === "auto" )
            $rm = 0;
          if ( $left === "auto" )
            $left = 0;
          if ( $right === "auto" )
            $right = 0;

          $width = $diff;

        } else if ( $left === "auto" ) {
          
          if ( $lm === "auto" )
            $lm = 0;
          if ( $rm === "auto" )
            $rm = 0;
          if ( $right === "auto" )
            $right = 0;

          $left = $diff;

        } else if ( $right === "auto" ) {

          if ( $lm === "auto" )
            $lm = 0;
          if ( $rm === "auto" )
            $rm = 0;

          $right = $diff;
        }

      } else {

        // Find auto properties and get them to take up the slack
        if ( $width === "auto" )
          $width = $diff;

        else if ( $lm === "auto" && $rm === "auto" )
          $lm = $rm = round($diff / 2);

        else if ( $lm === "auto" )
          $lm = $diff;

        else if ( $rm === "auto" )
          $rm = $diff;
      }

    } else if ($diff < 0) {

      // We are over constrained--set margin-right to the difference
      $rm = $diff;

    }

    return array("width"=> $width, "margin_left" => $lm, "margin_right" => $rm, "left" => $left, "right" => $right);
  }

  /** 
   * Call the above function, but resolve max/min widths
   * @return array
   */
  protected function _calculate_restricted_width() {
    $frame = $this->_frame;
    $style = $frame->get_style();
    $cb = $frame->get_containing_block();
    
    if ( $style->position === "fixed" )
      $cb = $frame->get_root()->get_containing_block();
    
    //if ( $style->position === "absolute" )
    //  $cb = $frame->find_positionned_parent()->get_containing_block();

    if ( !isset($cb["w"]) )
      throw new DOMPDF_Exception("Box property calculation requires containing block width");

    // Treat width 100% as auto
    if ( $style->width === "100%" ) {
      $width = "auto";
    }
    else {
      $width = $style->length_in_pt($style->width, $cb["w"]);
    }
    extract($this->_calculate_width($width));

    // Handle min/max width
    $min_width = $style->length_in_pt($style->min_width, $cb["w"]);
    $max_width = $style->length_in_pt($style->max_width, $cb["w"]);

    if ( $max_width !== "none" && $min_width > $max_width)
      // Swap 'em
      list($max_width, $min_width) = array($min_width, $max_width);

    if ( $max_width !== "none" && $width > $max_width )
      extract($this->_calculate_width($max_width));

    if ( $width < $min_width )
      extract($this->_calculate_width($min_width));

    return array($width, $margin_left, $margin_right, $left, $right);

  }
  
  /** 
   * Determine the unrestricted height of content within the block
   * by adding each line's height
   * @return float
   */
  protected function _calculate_content_height() {
    $height = 0;
    
    foreach ($this->_frame->get_lines() as $line) {
      $height += $line["h"];
    }

    return $height;
  }

  /** 
   * Determine the frame's restricted height
   * @return array
   */
  protected function _calculate_restricted_height() {
    $style = $this->_frame->get_style();
    $content_height = $this->_calculate_content_height();
    $cb = $this->_frame->get_containing_block();
    
    $height = $style->length_in_pt($style->height, $cb["h"]);

    $top    = $style->length_in_pt($style->top, $cb["h"]);
    $bottom = $style->length_in_pt($style->bottom, $cb["h"]);

    $margin_top    = $style->length_in_pt($style->margin_top, $cb["h"]);
    $margin_bottom = $style->length_in_pt($style->margin_bottom, $cb["h"]);

    if ( $style->position === "absolute" || $style->position === "fixed" ) {

      // see http://www.w3.org/TR/CSS21/visudet.html#abs-non-replaced-height

      $dims = array($top !== "auto" ? $top : 0,
                    $style->margin_top !== "auto" ? $style->margin_top : 0,
                    $style->padding_top,
                    $style->border_top_width,
                    $height !== "auto" ? $height : 0,
                    $style->border_bottom_width,
                    $style->padding_bottom,
                    $style->margin_bottom !== "auto" ? $style->margin_bottom : 0,
                    $bottom !== "auto" ? $bottom : 0);

      $sum = $style->length_in_pt($dims, $cb["h"]);

      $diff = $cb["h"] - $sum; 

      if ( $diff > 0 ) {

        if ( $height === "auto" && $top === "auto" && $bottom === "auto" ) {

          if ( $margin_top === "auto" ) 
            $margin_top = 0;
          if ( $margin_bottom === "auto" )
            $margin_bottom = 0;

          $height = $diff;

        } else if ( $height === "auto" && $top === "auto" ) {

          if ( $margin_top === "auto" ) 
            $margin_top = 0;
          if ( $margin_bottom === "auto" )
            $margin_bottom = 0;

          $height = $content_height;
          $top = $diff - $content_height;

        } else if ( $height === "auto" && $bottom === "auto" ) {

          if ( $margin_top === "auto" ) 
            $margin_top = 0;
          if ( $margin_bottom === "auto" )
            $margin_bottom = 0;

          $height = $content_height;
          $bottom = $diff - $content_height;

        } else if ( $top === "auto" && $bottom === "auto" ) {

          if ( $margin_top === "auto" ) 
            $margin_top = 0;
          if ( $margin_bottom === "auto" )
            $margin_bottom = 0;

          $bottom = $diff;

        } else if ( $top === "auto" ) {

          if ( $margin_top === "auto" ) 
            $margin_top = 0;
          if ( $margin_bottom === "auto" )
            $margin_bottom = 0;

          $top = $diff;

        } else if ( $height === "auto" ) {

          if ( $margin_top === "auto" ) 
            $margin_top = 0;
          if ( $margin_bottom === "auto" )
            $margin_bottom = 0;

          $height = $diff;

        } else if ( $bottom === "auto" ) {

          if ( $margin_top === "auto" ) 
            $margin_top = 0;
          if ( $margin_bottom === "auto" )
            $margin_bottom = 0;

          $bottom = $diff;

        } else {

          if ( $style->overflow === "visible" ) {

            // set all autos to zero
            if ( $margin_top === "auto" ) 
              $margin_top = 0;
            if ( $margin_bottom === "auto" )
              $margin_bottom = 0;
            if ( $top === "auto" )
              $top = 0;
            if ( $bottom === "auto" )
              $bottom = 0;
            if ( $height === "auto" )
              $height = $content_height;

          }

          // FIXME: overflow hidden
        }

      }

    } else {

      // Expand the height if overflow is visible 
      if ( $height === "auto" && $content_height > $height /* && $style->overflow === "visible" */) 
        $height = $content_height;

      // FIXME: this should probably be moved to a seperate function as per
      // _calculate_restricted_width
      
      // Only handle min/max height if the height is independent of the frame's content
      if ( !($style->overflow === "visible" ||
             ($style->overflow === "hidden" && $height === "auto")) ) {

        $min_height = $style->min_height;
        $max_height = $style->max_height;

        if ( isset($cb["h"]) ) {
          $min_height = $style->length_in_pt($min_height, $cb["h"]);
          $max_height = $style->length_in_pt($max_height, $cb["h"]);

        } else if ( isset($cb["w"]) ) {

          if ( mb_strpos($min_height, "%") !== false )
            $min_height = 0;
          else
            $min_height = $style->length_in_pt($min_height, $cb["w"]);

          if ( mb_strpos($max_height, "%") !== false )
            $max_height = "none";
          else
            $max_height = $style->length_in_pt($max_height, $cb["w"]);
        }

        if ( $max_height !== "none" && $min_height > $max_height )
          // Swap 'em
          list($max_height, $min_height) = array($min_height, $max_height);

        if ( $max_height !== "none" && $height > $max_height )
          $height = $max_height;

        if ( $height < $min_height )
          $height = $min_height;
      }

    }

    return array($height, $margin_top, $margin_bottom, $top, $bottom);

  }

  /**
   * Adjust the justification of each of our lines.
   * http://www.w3.org/TR/CSS21/text.html#propdef-text-align
   */
  protected function _text_align() {
    $style = $this->_frame->get_style();
    $w = $this->_frame->get_containing_block("w");
    $width = $style->length_in_pt($style->width, $w);
    switch ($style->text_align) {

    default:
    case "left":
      foreach ($this->_frame->get_lines() as $line) {
        if ( !$line["left"] ) continue;
        foreach($line["frames"] as $frame) {
          if ( $frame instanceof Block_Frame_Decorator) continue;
          $frame->set_position( $frame->get_position("x") + $line["left"] );
        }
      }
      return;

    case "right":
      foreach ($this->_frame->get_lines() as $line) {
        // Move each child over by $dx
        $dx = $width - $line["w"] - $line["right"];
        
        foreach($line["frames"] as $frame) {
          // Block frames are not aligned by text-align
          if ($frame instanceof Block_Frame_Decorator) continue;
          
          $frame->set_position( $frame->get_position("x") + $dx );
        }
      }
      break;


    case "justify":
      // We justify all lines except the last one
      $lines = $this->_frame->get_lines(); // needs to be a variable (strict standards)
      $lines = array_splice($lines, 0, -1);
      
      foreach($lines as $i => $line) {
        if ( $line["br"] ) {
          unset($lines[$i]);
        }
      }
      
      // One space character's width. Will be used to get a more accurate spacing
      $space_width = Font_Metrics::get_text_width(" ", $style->font_family, $style->font_size);
      
      foreach ($lines as $i => $line) {
        if ( $line["left"] ) {
          foreach($line["frames"] as $frame) {
            if ( !$frame instanceof Text_Frame_Decorator )
              continue;
  
            $frame->set_position( $frame->get_position("x") + $line["left"] );
          }
        }
          
        // Only set the spacing if the line is long enough.  This is really
        // just an aesthetic choice ;)
        //if ( $line["left"] + $line["w"] + $line["right"] > self::MIN_JUSTIFY_WIDTH * $width ) {
          
          // Set the spacing for each child
          if ( $line["wc"] > 1 )
            $spacing = ($width - ($line["left"] + $line["w"] + $line["right"]) + $space_width) / ($line["wc"] - 1);
          else
            $spacing = 0;

          $dx = 0;
          foreach($line["frames"] as $frame) {
            if ( !$frame instanceof Text_Frame_Decorator )
              continue;
              
            $text = $frame->get_text();
            $spaces = mb_substr_count($text, " ");
            
            $char_spacing = $style->length_in_pt($style->letter_spacing);
            $_spacing = $spacing + $char_spacing;
            
            $frame->set_position( $frame->get_position("x") + $dx );
            $frame->set_text_spacing($_spacing);
            
            $dx += $spaces * $_spacing;
          }

          // The line (should) now occupy the entire width
          $this->_frame->set_line($i, null, $width);

        //}
      }
      break;

    case "center":
    case "centre":
      foreach ($this->_frame->get_lines() as $line) {
        // Centre each line by moving each frame in the line by:
        $dx = ($width + $line["left"] - $line["w"] - $line["right"] ) / 2;
        
        foreach ($line["frames"] as $frame) {
          // Block frames are not aligned by text-align
          if ($frame instanceof Block_Frame_Decorator) continue;
          
          $frame->set_position( $frame->get_position("x") + $dx );
        }
      }
      break;
    }
  }
  
  /**
   * Align inline children vertically.
   * Aligns each child vertically after each line is reflowed
   */
  function vertical_align() {
    
    foreach ( $this->_frame->get_lines() as $i => $line ) {

      $height = $line["h"];
    
      foreach ( $line["frames"] as $frame ) {
        $style = $frame->get_style();

        if ( $style->display !== "inline" && $style->display !== "text" )
          continue;

        // FIXME?
        if ( $this instanceof Table_Cell_Frame_Reflower )
          $align = $frame->get_frame()->get_style()->vertical_align;
        else 
          $align = $frame->get_frame()->get_parent()->get_style()->vertical_align;
          
        $frame_h = $frame->get_margin_height();
        $y = $line["y"];
        
        switch ($align) {

        case "baseline":
          $y += $height - $frame_h;
          break;

        case "middle":
          $y += ($height + $frame_h) / 2;
          break;

        case "sub":
          $y += 0.2 * $height;
          break;

        case "super":
          $y += -0.3 * $height;
          break;

        case "text-top":
        case "top": // Not strictly accurate, but good enough for now
          break;

        case "text-bottom":
        case "bottom":
          $y += $height - $frame_h;
          break;
        }

        $x = $frame->get_position("x");
        $frame->set_position($x, $y);

      }
    }
  }

  function reflow(Frame_Decorator $block = null) {

    // Check if a page break is forced
    $page = $this->_frame->get_root();
    $page->check_forced_page_break($this->_frame);

    // Bail if the page is full
    if ( $page->is_full() )
      return;
      
    // Generated content
    $this->_set_content();

    // Collapse margins if required
    $this->_collapse_margins();

    $style = $this->_frame->get_style();
    $cb = $this->_frame->get_containing_block();
    
    if ( $style->counter_increment && ($increment = $style->counter_increment) !== "none" )
      $this->_frame->increment_counter($increment);
    
    if ( $style->position === "fixed" )
      $cb = $this->_frame->get_root()->get_containing_block();
    
    // Determine the constraints imposed by this frame: calculate the width
    // of the content area:
    list($w, $left_margin, $right_margin, $left, $right) = $this->_calculate_restricted_width();

    // Store the calculated properties
    $style->width = $w . "pt";
    $style->margin_left = $left_margin."pt";
    $style->margin_right = $right_margin."pt";
    $style->left = $left ."pt";
    $style->right = $right . "pt";
    
    // Update the position
    $this->_frame->position();
    list($x, $y) = $this->_frame->get_position();

    // Adjust the first line based on the text-indent property
    $indent = $style->length_in_pt($style->text_indent, $cb["w"]);
    $this->_frame->increase_line_width($indent);

    // Determine the content edge
    $top = $style->length_in_pt(array($style->margin_top,
                                      $style->padding_top,
                                      $style->border_top_width), $cb["h"]);

    $bottom = $style->length_in_pt(array($style->border_bottom_width,
                                         $style->margin_bottom,
                                         $style->padding_bottom), $cb["h"]);

    $cb_x = $x + $left_margin + $style->length_in_pt(array($style->border_left_width, 
                                                           $style->padding_left), $cb["w"]);

    $cb_y = $y + $top;

    $cb_h = ($cb["h"] + $cb["y"]) - $bottom - $cb_y;

    // Set the y position of the first line in this block
    $this->_frame->set_current_line($cb_y);
    
    $floating_children = array();
    
    // Set the containing blocks and reflow each child
    foreach ( $this->_frame->get_children() as $child ) {
      
      // Bail out if the page is full
      if ( $page->is_full() )
        break;

      // Floating siblings
      if ( DOMPDF_ENABLE_CSS_FLOAT && count($floating_children) ) {
        $offset_left = 0;
        $offset_right = 0;
        
        // We need to reflow the child to know its initial x position
        $child->set_containing_block($cb_x, $cb_y, $w, $cb_h);
        $child->reflow($this->_frame);
          
        $current_line = $this->_frame->get_current_line();
        
        foreach ( $floating_children as $child_key => $floating_child ) {
          $float = $floating_child->get_style()->float;
          $floating_width = $floating_child->get_margin_width();
          $floating_x = $floating_child->get_position("x");
          
          if ( $float === "left" ) {
            if ($current_line["left"] + $child->get_position("x") > $floating_x + $floating_width) continue;
          }
          else {
            if ($current_line["left"] + $child->get_position("x") + $child->get_margin_width() < $w - $floating_width - $current_line["right"]) continue;
          }
          
          // If the child is still shifted by the floating element
          if ( $floating_child->get_position("y") + $floating_child->get_margin_height() > $current_line["y"] ) {
            if ( $float === "left" )
              $offset_left += $floating_width;
            else
              $offset_right += $floating_width;
          }
          
          // else, the floating element won't shift anymore
          else {
            unset($floating_children[$child_key]);
          }
        }
        
        if ( $offset_left ) 
          $this->_frame->set_current_line(array("left" => $offset_left));
          
        if ( $offset_right )
          $this->_frame->set_current_line(array("right" => $offset_right));
      }
      
      $child->set_containing_block($cb_x, $cb_y, $w, $cb_h);
      $child->reflow($this->_frame);
      
      // Don't add the child to the line if a page break has occurred
      if ( $page->check_page_break($child) )
        break;
        
      $child_style = $child->get_style();
      
      if ( DOMPDF_ENABLE_CSS_FLOAT && $child_style->float !== "none") {
        $floating_children[] = $child;
        
        // Remove next frame's beginning whitespace
        $next = $child->get_next_sibling();
        if ( $next && $next instanceof Text_Frame_Decorator) {
          $next->set_text(ltrim($next->get_text()));
        }
        
        $float_x = $cb_x;
        $float_y = $this->_frame->get_current_line("y");
        
        $child_style = $child->get_style();
        
        switch( $child_style->float ) {
          case "left": break;
          case "right": 
            $width = $w;
            $float_x += ($width - $child->get_margin_width());
            break;
        }
        
        $child->set_position($float_x, $float_y);
      }
      
    }

    // Determine our height
    list($height, $margin_top, $margin_bottom, $top, $bottom) = $this->_calculate_restricted_height();
    $style->height = $height;
    $style->margin_top = $margin_top;
    $style->margin_bottom = $margin_bottom;
    $style->top = $top;
    $style->bottom = $bottom;

    $this->_text_align();
    $this->vertical_align();
    
    if ( $block ) {
      $block->add_frame_to_line($this->_frame);
    }
  }
}
