<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Catalog
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
 

/**
 * Catalog navigation
 *
 * @category   Mage
 * @package    Mage_Catalog
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Catalog_Block_Navigation extends Mage_Core_Block_Template
{
	protected $_categoryInstance = null;
	
	/**
	 * Current category key
	 *
	 * @var string
	 */
	protected $_currentCategoryKey;
	
	/**
	 * Array of level position counters
	 *
	 * @var array
	 */
	protected $_itemLevelPositions = array();
	
	protected function _construct()
	{
		$this->addData(array(
					'cache_lifetime'    => false,
					'cache_tags'        => array(Mage_Catalog_Model_Category::CACHE_TAG, Mage_Core_Model_Store_Group::CACHE_TAG),
					));
	}
	
	/**
	 * Get Key pieces for caching block content
	 *
	 * @return array
	 */
	public function getCacheKeyInfo()
	{
		$shortCacheId = array(
				'CATALOG_NAVIGATION',
				Mage::app()->getStore()->getId(),
				Mage::getDesign()->getPackageName(),
				Mage::getDesign()->getTheme('template'),
				Mage::getSingleton('customer/session')->getCustomerGroupId(),
				'template' => $this->getTemplate(),
				'name' => $this->getNameInLayout(),
				$this->getCurrenCategoryKey()
				);
		$cacheId = $shortCacheId;
		
		$shortCacheId = array_values($shortCacheId);
		$shortCacheId = implode('|', $shortCacheId);
		$shortCacheId = md5($shortCacheId);
		
		$cacheId['category_path'] = $this->getCurrenCategoryKey();
		$cacheId['short_cache_id'] = $shortCacheId;
		
		return $cacheId;
	}
	
	/**
	 * Get current category key
	 *
	 * @return mixed
	 */
	public function getCurrenCategoryKey()
	{
		if (!$this->_currentCategoryKey) {
			$category = Mage::registry('current_category');
			if ($category) {
				$this->_currentCategoryKey = $category->getPath();
			} else {
				$this->_currentCategoryKey = Mage::app()->getStore()->getRootCategoryId();
			}
		}
		
		return $this->_currentCategoryKey;
	}
	
	/**
	 * Get catagories of current store
	 *
	 * @return Varien_Data_Tree_Node_Collection
	 */
	public function getStoreCategories()
	{
		$helper = Mage::helper('catalog/category');
		return $helper->getStoreCategories();
	}
	
	/**
	 * Retrieve child categories of current category
	 *
	 * @return Varien_Data_Tree_Node_Collection
	 */
	public function getCurrentChildCategories()
	{
		$layer = Mage::getSingleton('catalog/layer');
		$category   = $layer->getCurrentCategory();
		/* @var $category Mage_Catalog_Model_Category */
		$categories = $category->getChildrenCategories();
		$productCollection = Mage::getResourceModel('catalog/product_collection');
		$layer->prepareProductCollection($productCollection);
		$productCollection->addCountToCategories($categories);
		return $categories;
	}
	
	/**
	 * Checkin activity of category
	 *
	 * @param   Varien_Object $category
	 * @return  bool
	 */
	public function isCategoryActive($category)
	{
		if ($this->getCurrentCategory()) {
			return in_array($category->getId(), $this->getCurrentCategory()->getPathIds());
		}
		return false;
	}
	
	protected function _getCategoryInstance()
	{
		if (is_null($this->_categoryInstance)) {
			$this->_categoryInstance = Mage::getModel('catalog/category');
		}
		return $this->_categoryInstance;
	}
	
	/**
	 * Get url for category data
	 *
	 * @param Mage_Catalog_Model_Category $category
	 * @return string
	 */
	public function getCategoryUrl($category)
	{
		if ($category instanceof Mage_Catalog_Model_Category) {
			$url = $category->getUrl();
		} else {
			$url = $this->_getCategoryInstance()
				->setData($category->getData())
				->getUrl();
		}
		
		return $url;
	}
	
	/**
	 * Return item position representation in menu tree
	 *
	 * @param int $level
	 * @return string
	 */
	protected function _getItemPosition($level)
	{
		if ($level == 0) {
			$zeroLevelPosition = isset($this->_itemLevelPositions[$level]) ? $this->_itemLevelPositions[$level] + 1 : 1;
			$this->_itemLevelPositions = array();
			$this->_itemLevelPositions[$level] = $zeroLevelPosition;
		} elseif (isset($this->_itemLevelPositions[$level])) {
			$this->_itemLevelPositions[$level]++;
		} else {
			$this->_itemLevelPositions[$level] = 1;
		}
		
		$position = array();
		for($i = 0; $i <= $level; $i++) {
			if (isset($this->_itemLevelPositions[$i])) {
				$position[] = $this->_itemLevelPositions[$i];
			}
		}
		return implode('-', $position);
	}
	
	/**
	 * Render category to html
	 *
	 * @param Mage_Catalog_Model_Category $category
	 * @param int Nesting level number
	 * @param boolean Whether ot not this item is last, affects list item class
	 * @param boolean Whether ot not this item is first, affects list item class
	 * @param boolean Whether ot not this item is outermost, affects list item class
	 * @param string Extra class of outermost list items
	 * @param string If specified wraps children list in div with this class
	 * @param boolean Whether ot not to add on* attributes to list item
	 * @return string
	 */
	protected function _renderCategoryMenuItemHtml($category, $level = 0, $isLast = false, $isFirst = false,
		$isOutermost = false, $outermostItemClass = '', $childrenWrapClass = '', $noEventAttributes = false)
	{
		if (!$category->getIsActive()) {
			return '';
		}
		$html = array();
		
		// get all children
		if (Mage::helper('catalog/category_flat')->isEnabled()) {
			$children = (array)$category->getChildrenNodes();
			$childrenCount = count($children);
		} else {
			$children = $category->getChildren();
			$childrenCount = $children->count();
		}
		$hasChildren = ($children && $childrenCount);
		
		// select active children
		$activeChildren = array();
		foreach ($children as $child) {
			if ($child->getIsActive()) {
				$activeChildren[] = $child;
			}
		}
		$activeChildrenCount = count($activeChildren);
		$hasActiveChildren = ($activeChildrenCount > 0);
		
		// prepare list item html classes
		$classes = array();
		$classes[] = 'level' . $level;
		$classes[] = 'nav-' . $this->_getItemPosition($level);
		if ($this->isCategoryActive($category)) {
			$classes[] = 'active';
		}
		$linkClass = '';
		if ($isOutermost && $outermostItemClass) {
			$classes[] = $outermostItemClass;
			$linkClass = ' class="'.$outermostItemClass.'"';
		}
		if ($isFirst) {
			$classes[] = 'first';
		}
		if ($isLast) {
			$classes[] = 'last';
		}
		if ($hasActiveChildren) {
			$classes[] = 'parent';
		}
		
		// prepare list item attributes
		$attributes = array();
		if (count($classes) > 0) {
			$attributes['class'] = implode(' ', $classes);
		}
		if ($hasActiveChildren && !$noEventAttributes) {
			$attributes['onmouseover'] = 'toggleMenu(this,1)';
			$attributes['onmouseout'] = 'toggleMenu(this,0)';
		}
		
		// assemble list item with attributes
		$htmlLi = '<li';
		foreach ($attributes as $attrName => $attrValue) {
			$htmlLi .= ' ' . $attrName . '="' . str_replace('"', '\"', $attrValue) . '"';
		}
		$htmlLi .= '>';
		$html[] = $htmlLi;
		
		//$html[] = '<a href="'.$this->getCategoryUrl($category).'"'.$linkClass.'>';
		$html[] = '<a href="#"'.$linkClass.'>';
		$html[] = '<span>' . $this->escapeHtml($category->getName()) . '</span>';
		$html[] = '</a>';
		
		// render children
		$htmlChildren = '';
		$j = 0;
		foreach ($activeChildren as $child) {
			$htmlChildren .= $this->_renderCategoryMenuItemHtml(
					$child,
					($level + 1),
					($j == $activeChildrenCount - 1),
					($j == 0),
					false,
					$outermostItemClass,
					$childrenWrapClass,
					$noEventAttributes
					);
			$j++;
		}
		if (!empty($htmlChildren)) {
			if ($childrenWrapClass) {
				$html[] = '<div class="' . $childrenWrapClass . '">';
			}
			$html[] = '<ul class="level' . $level . '">';
			$html[] = $htmlChildren;
			$html[] = '</ul>';
			if ($childrenWrapClass) {
				$html[] = '</div>';
			}
		}
		
		$html[] = '</li>';
		
		$html = implode("\n", $html);
		return $html;
	}
	
	/**
	 * Render category to html
	 *
	 * @deprecated deprecated after 1.4
	 * @param Mage_Catalog_Model_Category $category
	 * @param int Nesting level number
	 * @param boolean Whether ot not this item is last, affects list item class
	 * @return string
	 */
	public function drawItem($category, $level = 0, $last = false)
	{
		return $this->_renderCategoryMenuItemHtml($category, $level, $last);
	}
	
	/**
	 * Enter description here...
	 *
	 * @return Mage_Catalog_Model_Category
	 */
	public function getCurrentCategory()
	{
		if (Mage::getSingleton('catalog/layer')) {
			return Mage::getSingleton('catalog/layer')->getCurrentCategory();
		}
		return false;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return string
	 */
	public function getCurrentCategoryPath()
	{
		if ($this->getCurrentCategory()) {
			return explode(',', $this->getCurrentCategory()->getPathInStore());
		}
		return array();
	}
	
	/**
	 * Enter description here...
	 *
	 * @param Mage_Catalog_Model_Category $category
	 * @return string
	 */
	public function drawOpenCategoryItem($category) {
		$html = '';
		if (!$category->getIsActive()) {
			return $html;
		}
		
		$html.= '<li';
		
		if ($this->isCategoryActive($category)) {
			$html.= ' class="active"';
		}
		
		$html.= '>'."\n";
		$html.= '<a href="'.$this->getCategoryUrl($category).'"><span>'.$this->htmlEscape($category->getName()).'</span></a>'."\n";
		
		if (in_array($category->getId(), $this->getCurrentCategoryPath())){
			$children = $category->getChildren();
			$hasChildren = $children && $children->count();
			
			if ($hasChildren) {
				$htmlChildren = '';
				foreach ($children as $child) {
					$htmlChildren.= $this->drawOpenCategoryItem($child);
				}
				
				if (!empty($htmlChildren)) {
					$html.= '<ul>'."\n"
						.$htmlChildren
						.'</ul>';
				}
			}
		}
		$html.= '</li>'."\n";
		return $html;
	}
	
	/**
	 * Render categories menu in HTML
	 *
	 * @param int Level number for list item class to start from
	 * @param string Extra class of outermost list items
	 * @param string If specified wraps children list in div with this class
	 * @return string
	 */
	public function renderCategoriesMenuHtml($level = 0, $outermostItemClass = '', $childrenWrapClass = '')
	{
		$activeCategories = array();
		foreach ($this->getStoreCategories() as $child) {
			if ($child->getIsActive()) {
				$activeCategories[] = $child;
			}
		}
		$activeCategoriesCount = count($activeCategories);
		$hasActiveCategoriesCount = ($activeCategoriesCount > 0);
		
		if (!$hasActiveCategoriesCount) {
			return '';
		}
		
		$html = '';
		$j = 0;
		foreach ($activeCategories as $category) {
			$html .= $this->_renderCategoryMenuItemHtml(
					$category,
					$level,
					($j == $activeCategoriesCount - 1),
					($j == 0),
					true,
					$outermostItemClass,
					$childrenWrapClass,
					true
					);
			$j++;
		}
		
		return $html;
	}
	
	public function timeBetweenNowAndDeadline($deadline) {
		//$deadline = strtotime($deadline);
		$currentDate = date("U");
		$diff = $deadline-$currentDate;
		$seconds = 0;
		$hours   = 0;
		$minutes = 0;
		
		if($diff % 86400 <= 0){$days = $diff / 86400;}  // 86,400 seconds in a day
		if($diff % 86400 > 0)
		{
			$rest = ($diff % 86400);
			$days = ($diff - $rest) / 86400;
			if($rest % 3600 > 0)
			{
				$rest1 = ($rest % 3600);
				$hours = ($rest - $rest1) / 3600;
				if($rest1 % 60 > 0)
				{
					$rest2 = ($rest1 % 60);
					$minutes = ($rest1 - $rest2) / 60;
					$seconds = $rest2;
				}
				else{$minutes = $rest1 / 60;}
			}
			else{$hours = $rest / 3600;}
		}
		
		if($days > 0){$days = $days.' days';}
		else{$days = false;}
		if($hours > 0){
			if($days!=false){
				$hours = ' and '.$hours.' hours ';
			}
			else{
				$hours = $hours.' hours ';
			}
		}
		else{$hours = false;}
		if($minutes > 0){$minutes = $minutes.' minutes';}
		else{$minutes = false;}
		$seconds = $seconds.'s'; // always be at least one second
		
		if($days==0 && $hours == 0 && $minutes == 0){
			return 0;
		}
		elseif($days==0 && $hours == 0){
			return $minutes;
		}
		else{
			return $days.''.$hours;	
		}
		
	}
	public function menuToplevel()
	{
		$i = 1;
		$j = 1;
		$categories = $this->getStoreCategories();
		$twodays = strtotime(date('y-m-d H:G:00', strtotime("+2 days")));
		$today = strtotime(date('y-m-d H:i:s'));
		foreach ($categories as $_category){
			if($_category->hasChildren()){
				
				if($_category->getId() !=5){
					//enable the parent category URL
					//echo '<li><a href="'.$_category->getURL().'">' . $_category->getName() . '</a>';
					//disable the parent category URL
					echo '<li><a href="#">' . $_category->getName() . '</a>';
				}else{
					//enable parent category link
					//echo '<li class="last"><a href="'.$_category->getURL().'">' . $_category->getName() . '</a>';
					// disable parent category link
					echo '<li class="last"><a href="#">' . $_category->getName() . '</a>';				}
				echo '<div><ul>';
				//$_category->getId(); // category id
				
				$subcategory = $_category->getChildren();
				$child_id_explode = explode(",",$subcategory);
				
				// Sales orders are arranging the Subcategories position wise.
				$subCategoriesSorted = array(); //We're going to make an array of the sub cats, with the array key being the position (set in the back end by dragging), then we can sort by key.
				foreach($child_id_explode as $subCategoryId)
				{
					$cat = Mage::getModel('catalog/category')->load($subCategoryId);
					if($cat->getIsActive())
					{
						$subCategoriesSorted[$cat->getPosition()] = $cat;
					}
				}
				ksort($subCategoriesSorted);	
				
				if($_category->getId() !=5){
					//foreach($_category->getChildren() as $subcategory){
					//$c = Mage::getModel('catalog/category')->load($subcategory->getId());
																											
					
					foreach($subCategoriesSorted as $c)
					{    
						if (!$c->getSale_start_date() == null) {
							$startdate = strtotime(date('y-m-d', strtotime($c->getSale_start_date())) . ' ' . $c->getSale_start_time() . ':00');
							$enddate = strtotime(date('y-m-d', strtotime($c->getSale_end_date())) . ' ' . $c->getSale_end_time() . ':00');
							
							if ($this->timeBetweenNowAndDeadline($enddate) != 0 && $enddate > $twodays && $startdate < $today) {
								if ($i == 1) {
									echo '<li class="navSectTitle">New Sales</li>';
									echo '<li><a href="'. $c->getURL() .'">' . $c->getName() . '</a></li>';
								} else {
									echo '<li><a href="'. $c->getURL() .'">' . $c->getName() . '</a></li>';
								}
								$i++;
							}
						}
						
					}
					$i=1;
					
					echo '</ul><ul>';
					//foreach($_category->getChildren() as $subcategory){
					//$c = Mage::getModel('catalog/category')->load($subcategory->getId());
					/*$subcategory = $_category->getChildren();
						$child_id_explode = explode(",",$subcategory);		
					
					// Sales orders are arranging the Subcategories position wise.
					$subCategoriesSorted = array(); //We're going to make an array of the sub cats, with the array key being the position (set in the back end by dragging), then we can sort by key.
					foreach($child_id_explode as $subCategoryId)
					{
						$cat = Mage::getModel('catalog/category')->load($subCategoryId);
							if($cat->getIsActive())
							{
								$subCategoriesSorted[$cat->getPosition()] = $cat;
							}
						}
					ksort($subCategoriesSorted);		*/																					
					
					foreach($subCategoriesSorted as $c)
					{    
						if (!$c->getSale_start_date() == null) {
							$startdate = date('y-m-d', strtotime($c->getSale_start_date())) . ' ' . $c->getSale_start_time() . ':00';
							
							$enddate = strtotime(date('y-m-d', strtotime($c->getSale_end_date())) . ' ' . $c->getSale_end_time() . ':00');
							if ($this->timeBetweenNowAndDeadline($enddate) != 0 && $enddate < $twodays && $startdate < $today) {
								if ($j == 1) {
									echo '<li class="navSectTitle">Ending Soon</li>';
									echo '<li><a href="'. $c->getURL() .'">' . $c->getName() . '</a></li>';
								} else {
									echo '<li><a href="'. $c->getURL() .'">' . $c->getName() . '</a></li>';
								}
								$j++;
							}
						}
					}
				}else{
					//foreach($_category->getChildren() as $subcategory){
					//$c = Mage::getModel('catalog/category')->load($subcategory->getId());
					/*$subcategory = $_category->getChildren();
					$child_id_explode = explode(",",$subcategory);	
					
					// Sales orders are arranging the Subcategories position wise.
					$subCategoriesSorted = array(); //We're going to make an array of the sub cats, with the array key being the position (set in the back end by dragging), then we can sort by key.
					foreach($child_id_explode as $subCategoryId)
					{
						$cat = Mage::getModel('catalog/category')->load($subCategoryId);
						if($cat->getIsActive())
						{
							$subCategoriesSorted[$cat->getPosition()] = $cat;
						}
					}
					ksort($subCategoriesSorted);																							
					*/
					
					foreach($subCategoriesSorted as $subcategory)
					{    
						echo '<li><a href="'. $subcategory->getURL() .'">' . $subcategory->getName() . '</a></li>';
					}
				}
				$j=1;
				
				echo '</ul></div></li>';
			}
		} 
		
	}	
}
