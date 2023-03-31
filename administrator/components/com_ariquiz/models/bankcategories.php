<?php
/*
 *
 * @package		ARI Quiz
 * @author		ARI Soft
 * @copyright	Copyright (c) 2011 www.ari-soft.com. All rights reserved
 * @license		GNU/GPL (http://www.gnu.org/copyleft/gpl.html)
 * 
 */

(defined('_JEXEC') && defined('ARI_FRAMEWORK_LOADED')) or die;

AriKernel::import('Joomla.Models.Model');
AriKernel::import('Joomla.Database.DBUtils');
AriKernel::import('Utils.ArrayHelper');

class AriQuizModelBankcategories extends AriModel 
{
	function getCategoryCount($filter = null)
	{
		$db =& $this->getDBO();

		$query = AriDBUtils::getQuery();
		$query->select('COUNT(*)');
		$query->from('#__ariquizbankcategory');

		$db->setQuery((string)$query);
		$count = $db->loadResult();

		if ($db->getErrorNum())
		{
			JError::raiseError(
				500, 
				JText::sprintf(
					'COM_ARIQUIZ_ERROR_SQL_QUERY', 
					__CLASS__ . '::' . __FUNCTION__ . '()', 
					$query, 
					$db->getErrorMsg()
				)
			);
			
			return 0;
		}

		return $count;
	}
	
	function getCategoryList($filter = null)
	{
		$db =& $this->getDBO();
		if (empty($filter))
			$filter = new AriDataFilter();

		$query = AriDBUtils::getQuery();
		$query->select(
			array(
				'CategoryId',
				'CategoryName'
			)
		);
		$query->from('#__ariquizbankcategory');
		$query = $filter->applyToQuery($query);

		$db->setQuery((string)$query, $filter->getConfigValue('startOffset'), $filter->getConfigValue('limit'));
		$categories = $db->loadObjectList();

		if ($db->getErrorNum())
		{
			JError::raiseError(
				500, 
				JText::sprintf(
					'COM_ARIQUIZ_ERROR_SQL_QUERY', 
					__CLASS__ . '::' . __FUNCTION__ . '()', 
					$query, 
					$db->getErrorMsg()
				)
			);
			
			return null;
		}
		
		return $categories;
	}
	
	function deleteCategory($idList, $deleteQuestions = false, $newCategoryId = 0)
	{
		$idList = AriArrayHelper::toInteger($idList, 1);
		if (count($idList) == 0) 
			return false;
		
		$db =& $this->getDBO();
		
		$catStr = join(',', $idList);
		$query = array();
		$query[] = sprintf('DELETE FROM #__ariquizbankcategory WHERE CategoryId IN (%1$s)', 
			$catStr);

		if ($deleteQuestions)
		{
			$query2 = sprintf('SELECT QQ.QuestionId' .
				' FROM #__ariquizquestion QQ' .
				' WHERE QQ.QuizId = 0 AND QQ.QuestionCategoryId IN (%s)',
				$catStr);
			$db->setQuery($query2);
			$queIdList = J3_0 ? $db->loadColumn() : $db->loadResultArray();
			if ($db->getErrorNum())
			{
				JError::raiseError(
					500, 
					JText::sprintf(
						'COM_ARIQUIZ_ERROR_SQL_QUERY', 
						__CLASS__ . '::' . __FUNCTION__ . '()', 
						$db->getQuery(), 
						$db->getErrorMsg()
					)
				);
				
				return false;
			}

			if (is_array($queIdList) && count($queIdList) > 0)
			{
				$bankModel = AriModel::getInstance('Bankquestions', $this->getFullPrefix());
				if (!$bankModel->deleteQuestions($queIdList))
				{
					return false;
				}
			}
		}

		$query[] = sprintf(
			'UPDATE #__ariquizquestion QQ LEFT JOIN #__ariquizquestionversion QQV' .
			'	ON QQ.QuestionVersionId = QQV.QuestionVersionId' . 
			' SET QQ.QuestionCategoryId = %2$d, QQV.QuestionCategoryId = %2$d' . 
			' WHERE QQ.QuizId = 0 AND QQ.QuestionCategoryId IN (%1$s)',
			$catStr,
			$newCategoryId
		);

		foreach ($query as $queryItem)
		{
			$db->setQuery($queryItem);
			$db->query();
			if ($db->getErrorNum())
			{
				JError::raiseError(
					500, 
					JText::sprintf(
						'COM_ARIQUIZ_ERROR_SQL_QUERY', 
						__CLASS__ . '::' . __FUNCTION__ . '()', 
						$db->getQuery(), 
						$db->getErrorMsg()
					)
				);
				
				return false;
			}
		}

		return true;
	}
	
	function getCategoryMapping($categoryNames)
	{
		$db =& $this->getDBO();
		
		$categoryMapping = array();
		if (!is_array($categoryNames) || count($categoryNames) == 0)
			return $categoryMapping;

		$query = sprintf('SELECT CategoryId,CategoryName FROM #__ariquizbankcategory WHERE CategoryName IN (%s)',
			join(',', AriDBUtils::quote($categoryNames))
		);
		$db->setQuery($query);
		$categories = $db->loadAssocList();
		if ($db->getErrorNum())
		{
			JError::raiseError(
				500, 
				JText::sprintf(
					'COM_ARIQUIZ_ERROR_SQL_QUERY', 
					__CLASS__ . '::' . __FUNCTION__ . '()', 
					$query, 
					$db->getErrorMsg()
				)
			);
			
			return $categoryMapping;
		}
		
		foreach ($categories as $category)
		{
			$categoryMapping[$category['CategoryName']] = $category['CategoryId'];
		}

		return $categoryMapping;
	}
}