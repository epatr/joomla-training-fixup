<?php
/*
 *
 * @package		ARI Quiz
 * @author		ARI Soft
 * @copyright	Copyright (c) 2011 www.ari-soft.com. All rights reserved
 * @license		GNU/GPL (http://www.gnu.org/copyleft/gpl.html)
 *
 */

defined('_JEXEC') or die ('Restricted access');

require_once JPATH_ADMINISTRATOR . '/components/com_ariquiz/kernel/class.AriKernel.php';

require_once JPATH_ADMINISTRATOR . '/components/com_ariquiz/defines.php';
require_once JPATH_ADMINISTRATOR . '/components/com_ariquiz/models/quiz.php';
require_once JPATH_ADMINISTRATOR . '/components/com_ariquiz/tables/quiz.php';

AriKernel::import('Data.DataFilter');
AriKernel::import('Xml.XmlHelper');

JHtml::_('behavior.modal', 'a.modal');

class JElementSelectquiz extends JElement
{
    protected $type = 'Selectquiz';

    function fetchElement($name, $value, &$node, $control_name)
    {
        $lang = JFactory::getLanguage();
        $lang->load('com_ariquiz.sys', JPATH_ADMINISTRATOR);

        $id = $control_name . $name;
        $lblId = $id . '_label';
        $btnLbl = AriXmlHelper::getAttribute($node, 'btn_label', 'COM_ARIQUIZ_LABEL_SELECT');
        $btnLbl = JText::_($btnLbl);
        $showClearBtn = (bool)AriXmlHelper::getAttribute($node, 'hide_clear_btn', true);
        $btnClearLbl = AriXmlHelper::getAttribute($node, 'btn_clear_label', 'COM_ARIQUIZ_LABEL_CLEAR');
        $btnClearLbl = JText::_($btnClearLbl);
        $lbl = $emptyLabel = JText::_(AriXmlHelper::getAttribute($node, 'empty_label', ''));
        $ignoreQuizId = AriXmlHelper::getAttribute($node, 'ignore_quiz', '');

        $quizId = 0;
        if ($value)
        {
            $quizId = intval($value, 10);
            $model = AriModel::getInstance('Quiz', 'AriQuizModel');
            $quiz = $model->getQuiz($quizId);
            if ($quiz)
            {
                $lbl = $quiz->QuizName;
            }
            else
            {
                $quizId = 0;
            }
        }

        $this->registerScripts($name, $id, $lblId, $emptyLabel);

        if (J3_0)
            return sprintf(
                '<span class="input-append"><input class="input-medium" type="text" id="%5$s" value="%6$s" readonly="readonly" disabled="disabled" /><a target="blank" class="modal btn" rel="{handler: \'iframe\',size: {x: 700, y: 400}}" href="index.php?option=com_ariquiz&view=selectquiz&tmpl=component&callback=selectQuiz_%1$s_init&ignoreQuizId=%9$s"><i class="icon-edit"></i> %7$s</a>%8$s</span>
				<input type="hidden" name="%2$s[%1$s]" id="%3$s" value="%4$s" />
				',
                $name,
                $control_name,
                $id,
                $quizId,
                $lblId,
                $lbl,
                $btnLbl,
                $showClearBtn
                    ? sprintf(
                    '<a onclick="selectQuiz_%1$s_clear();return false;" href="#" title="" class="btn hasTooltip"><i class="icon-remove"></i></a>',
                    $name
                )
                    : '',
                $ignoreQuizId
            );
        else
            return sprintf(
                '<div class="fltlft"><input type="text" id="%5$s" value="%6$s" readonly="readonly" disabled="disabled" /></div>
				<div class="button2-left">
					<div class="blank">
						<a target="blank" class="modal btn" rel="{handler: \'iframe\',size: {x: 700, y: 400}}" href="index.php?option=com_ariquiz&view=selectquiz&tmpl=component&callback=selectQuiz_%1$s_init&ignoreQuizId=%9$s"><i class="icon-edit"></i> %7$s</a>
					</div>
				</div>
				%8$s
				<input type="hidden" name="%2$s[%1$s]" id="%3$s" value="%4$s" />
				',
                $name,
                $control_name,
                $id,
                $quizId,
                $lblId,
                $lbl,
                $btnLbl,
                $showClearBtn
                    ? sprintf(
                    '<div class="button2-left">
					        <div class="blank">
						        <a class="btn" href="#" onclick="selectQuiz_%1$s_clear();return false;">%2$s</a>
					        </div>
				        </div>',
                    $name,
                    $btnClearLbl
                )
                    : '',
                $ignoreQuizId
            );
    }

    function registerScripts($name, $ctrlId, $lblId, $emptyLbl)
    {
        $doc = JFactory::getDocument();

        $doc->addScriptDeclaration(
            sprintf(
                ';function selectQuiz_%1$s_init(context) {
					var quizManager = context.YAHOO.ARISoft.page.pageManager.quizManager;

					quizManager.quizSelectedEvent.subscribe(function(event, data) {
						var quizData = data[0];

						document.getElementById("%2$s").value = quizData.Id;
						document.getElementById("%3$s").value = quizData.Name;

						SqueezeBox.close();
					});
				};
				;function selectQuiz_%1$s_clear() {
				    document.getElementById("%2$s").value = "0";
					document.getElementById("%3$s").value = "%4$s";
				};',
                $name,
                $ctrlId,
                $lblId,
                $emptyLbl
            )
        );
    }
}