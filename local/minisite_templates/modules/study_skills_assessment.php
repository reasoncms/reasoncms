<?php

reason_include_once('minisite_templates/modules/default.php');

include_once(DISCO_INC . 'disco.php');

$GLOBALS['_module_class_names'][basename(__FILE__, '.php')] = 'StudySkillsAssessmentModule';

class StudySkillsAssessmentForm extends Disco {

    var $_log_errors = true;
    var $error;
    var $elements = array(
        
        // Motivation and Responsibility
        'M&R_header' => array(
            'type' => 'comment',
            'text' => '<h3> Motivation and Responsibility </h3>',
        ),
        'M&R1_question' => array(
            'type' => 'comment',
            'text' => 'I have high expecations of myself',
        ),
        'M&R1' => array(
            'type' => 'radio_scale',
            'display_name' => '&nbsp;',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'M&R2' => array(
            'type' => 'radio_scale',
            'display_name' => 'I work hard at everything I do',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'M&R3' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I set goals for myself which are ambitious and attainable',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'M&R4' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I take purposeful actions to achieve my goals and dreams',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'M&R5' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I am persistent',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'M&R6' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I seek help when I need it',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'M&R7' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I place great value on earning my college degree',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'M&R8' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I take responsibility for my actions (or inaction), rather than making excuses or putting the blame
                               elsewhere',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'M&R9' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I know how to change habits of mine that hinder my success',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'M&R10' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I keep promises that I make to myself or to others',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'M&R11' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'When I get off track, I realize it right away',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        
        // Organization
        'org_header' => array(
            'type' => 'comment',
            'text' => '<h3> Organization </h3>',
        ),
        'ORG_1' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I am on time',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'ORG_2' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I plan ahead',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'ORG_3' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I keep track of what I need to do',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'ORG_4' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I keep everything (handouts, assignments, papers, etc.) organized in a folder or binder by class',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'ORG_5' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I remember where I put something when I need it',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'ORG_6' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I am aware of deadlines and meet them',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'ORG_7' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'Others consider me to be dependable',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        
        // Self Confidence and Social Interaction
        'SC&SI_header' => array(
            'type' => 'comment',
            'text' => '<h3> Self confidence and Social Interactions </h3>',
        ),
        'SC&SI_1' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I believe in myself',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'SC&SI_2' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I am a positive person',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'SC&SI_3' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I expect to do well in my college classes',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'SC&SI_4' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I need to be around other people',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'SC&SI_5' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I am comfortable around other people',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'SC&SI_6' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I make friends easily',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'SC&SI_7' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I am aware of thoughts or beliefs I have that hinder my success',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'SC&SI_8' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I seek out opportunities that will help me grow as a person',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        
        // Healthy Living
        'HL_header' => array(
            'type' => 'comment',
            'text' => '<h3>  Healthy Living </h3>',
        ),
        'HL_1' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I get plenty of sleep at night and feel rested in the morning',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'HL_2' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I eat breakfast',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'HL_3' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I engage in regular physical activity',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'HL_4' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I am conscientious about eating healthy food',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'HL_5' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I avoid consumption of alcohol or other drugs',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'HL_6' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I feel happy and fully alive',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'HL_7' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'When I’m angry, sad, or afraid, I know how to manage my emotions so I don’t do or say
                               anything I’ll regret later',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'HL_8' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I handle stress well',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        
        // General Learning Strategies
        'GLS_header' => array(
            'type' => 'comment',
            'text' => '<h3>  General Learning Strategies </h3>',
        ),
        'GLS_1' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I make studying a top priority',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'GLS_2' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I read and understand the syllabus for each class',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'GLS_3' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I communicate personally with my instructors/professors',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'GLS_4' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I am prepared for my classes',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'GLS_5' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I study in a quiet place where I can concentrate',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'GLS_6' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'When I study, I am able to concentrate for at least 30 minutes at a time',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'GLS_7' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I study as I go, putting in time to understand how the pieces fit together',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'GLS_8' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I learn with the intention of remembering and applying the information learned',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'GLS_9' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I read all assigned materials, including supplemental materials not discussed in class',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'GLS_10' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I readily recall those things which I have studied',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'GLS_11' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I use sensory inputs to reinforce my learning (visual aids, mnemonic devices, flash cards, audio
                               recordings, etc.)',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'GLS_12' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'When I take a difficult class, I find a study partner or join a study group',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'GLS_13' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I know how to think critically and analytically about complex topics',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        
        // Writing
        'WR_header' => array(
            'type' => 'comment',
            'text' => '<h3>  Writing </h3>',
        ),
        'WR_1' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I am able to express my thoughts well in writing.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'WR_2' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I put aside a written assignment for a day or so, then rewrite it.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'WR_3' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I review my writing carefully for errors.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'WR_4' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I have someone else read my written work and consider their suggestions for improvement.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'WR_5' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I am comfortable using library sources for research.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'WR_6' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I am able to narrow a topic for an essay, research paper, etc.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'WR_7' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I allow sufficient time to collect information, organize material, write, and rewrite the
                               assignment.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        
        // Active Reading
        'AR_header' => array(
            'type' => 'comment',
            'text' => '<h3>  Active Reading </h3>',
        ),
        'AR_1' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I survey each chapter before I begin reading',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'AR_2' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I review reading materials more than once',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'AR_3' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'When learning new material, I summarize it in my own words',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'AR_4' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I am comfortable with my reading rate',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'AR_5' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'When reading, I can distinguish readily between important and unimportant points',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'AR_6' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I take notes and/or highlight important parts while reading',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        
        // Notetaking
        'AR_header' => array(
            'type' => 'comment',
            'text' => '<h3>  Notetaking </h3>',
        ),
        'AR_1' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'While I am taking notes, I think about how I will use them later.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'AR_2' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I understand the lecture and classroom discussion while I am taking notes.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'AR_3' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I write down the main points, rather than trying to record every word I hear.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'AR_4' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I organize my notes in some meaningful manner (outline, concept map, etc.)',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'AR_5' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I review my class notes within a few hours of taking them.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'AR_6' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I am able to understand and learn from my notes.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        
        // Testing
        'TST_header' => array(
            'type' => 'comment',
            'text' => '<h3>  Testing </h3>',
        ),
        'TST_1' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I try to find out what an exam will cover and how it will be graded.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'TST_2' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I get a good night’s rest prior to a scheduled exam.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'TST_3' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I avoid cramming for an exam.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'TST_4' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I am prepared for my exams.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'TST_5' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I follow directions carefully when taking an exam.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'TST_6' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I take time to understand the exam questions before starting to answer.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'TST_7' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I am calmly able to recall what I know during an exam.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'TST_8' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I understand the structure of different types of tests and am able to prepare for each type.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        
        // Classroom Engagement
        'CE_header' => array(
            'type' => 'comment',
            'text' => '<h3>  Classroom Engagement </h3>',
        ),
        'CE_1' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I attend class regularly.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'CE_2' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I sit near the front of the class whenever possible.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'CE_3' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I give non-verbal signals in class that I am interested (eye contact, ready to take notes, book
                               open, etc.).',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'CE_4' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I volunteer answers to questions posed by my instructor during class.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'CE_5' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I participate in class discussions.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'CE_6' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I take the initiative in group activities.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'CE_7' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'After each class, I can identify the major points and understand why they are important to the
                               material being covered.',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        
        // Time Management
        'TM_header' => array(
            'type' => 'comment',
            'text' => '<h3>  Time Management </h3>',
        ),
        'TM_1' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I avoid putting things off that need to be done',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'TM_2' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'When studying, I make a point to avoid distractions and interruptions',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'TM_3' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I use prime time (when I am most alert) for studying',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'TM_4' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I devote sufficient study time to each of my courses',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'TM_5' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I utilize monthly and weekly calendars',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'TM_6' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I avoid activities which tend to interfere with my planned schedule',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'TM_7' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I begin major course assignments well in advance',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'TM_8' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I break larger assignments into smaller tasks with specific deadlines',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'TM_9' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I spend most of my time doing important things',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'TM_10' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I have time to relax and have fun',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        
        // Money Management
        'MM_header' => array(
            'type' => 'comment',
            'text' => '<h3> Money Management </h3>',
        ),
        'MM_1' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I pay my bills on time',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'MM_2' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I track my spending',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'MM_3' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I control my spending by distinguishing between wants and needs',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'MM_4' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I think about how my credit score is affected by my money management habits',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'MM_5' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I stick to a budget I have set for myself',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'MM_6' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I save/invest some of my money',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'MM_7' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I take the initiative to understand my financial aid package',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        'MM_8' => array(
            'type' => 'radio_inline_no_sort',
            'display_name' => 'I seek additional forms of financial assistance to minimize my debt',
            'options' => array('0' => 'Never', '1' => 'Sometimes', '2' => 'Usually', '3' => 'Always'),
        ),
        
    );
    function on_every_time(){
        //$this->set_value('MM_6', 2);
    }
    
    
     

}

class StudySkillsAssessmentModule extends DefaultMinisiteModule {
    var $my_form;
    function run() {

        $this->my_form = new StudySkillsAssessmentForm();
        $this->my_form->run();

        
        
        //***
        // $this->calculate_score();
    }

    function calculate_score(){
        //$values = $this->my_form->get_values();
        //pray($values);
        //$this->my_form->set_value('MM_6', 2);
        //$value1 = $this->my_form->get_value('MM_6');
        //$value1 = get_value($test1);
        //pray($test1);
       // pray($value1);
        
        //echo 'hi there' . $value1;
        
        // -- Motivation and Responsibility calc
        $MR1_value = $this->my_form->get_value('M&R1');
        $MR2_value = $this->my_form->get_value('M&R2');
        $MR3_value = $this->my_form->get_value('M&R3');
        $MR4_value = $this->my_form->get_value('M&R4');
        $MR5_value = $this->my_form->get_value('M&R5');
        $MR6_value = $this->my_form->get_value('M&R6');
        $MR7_value = $this->my_form->get_value('M&R7');
        $MR8_value = $this->my_form->get_value('M&R8');
        $MR9_value = $this->my_form->get_value('M&R9');
        $MR10_value = $this->my_form->get_value('M&R10');
        $MR11_value = $this->my_form->get_value('M&R11');
        
        $MR_sum = $MR1_value + $MR2_value + $MR3_value + $MR4_value + $MR5_value + $MR6_value + $MR7_value + $MR8_value+ $MR9_value+ $MR10_value+ $MR11_value;
        
        $MR_txt = '<h3>Motivation and Reponsiblity</h3>';
        
        
        if ($MR_sum < 22 ) {
            
            $MR_txt .= '<p>You could take greater ownership in your
                        educational and personal success. Talk to your SSS Advisor about discovering your
                        purpose and creating the outcomes you desire.</p>';
            
        }
        
        
        

    }

}

include_once('/usr/local/webapps/reason/reason_package/disco/plasmature/types/options.php');
class radio_scaleType extends radio_inline_no_sortType
{
    var $type = 'radio_scale';

    function get_display()
    {
        $i = 0;
        $str = '<div id="'.$this->name.'_container" class="radioButtons scaleRadioButtons">'."\n";
        foreach( $this->options as $key => $val )
        {
            $id = 'radio_scale_'.$this->name.'_'.$i++;
            $str .= '<span class="radioItem"><span class="radioButton"><input type="radio" id="'.$id.'" name="'.$this->name.'" value="'.htmlspecialchars($key, ENT_QUOTES).'"';
            if ( $key == $this->value )
                $str .= ' checked="checked"';
            $str .= ' /></span> <label for="'.$id.'">'.$val.'</label></span> '."\n";
        }
        $str .= '</div>'."\n";
        return $str;
    }
}

?>
