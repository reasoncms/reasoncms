<?php

//include_once(WEB_PATH.'reason/local/stock/study_skills_assessment.php');
include_once(TYR_INC . 'tyr.php');

class StudySkillsAssessmentTwoForm extends FormStep {

    var $my_form;

    function run() {

    }

    function on_every_time() {
        $this->show_form = false;
        $this->my_form = new StudySkillsAssessmentOneForm();

        $this->calculate_score();
    }

    function calculate_score() {
        $myelements = $this->my_form->elements;

        $MR_group = array_slice($myelements, 1, 11);
        $ORG_group = array_slice($myelements, 13, 7);
        $SC_SI_group = array_slice($myelements, 21, 8);
        $HL_group = array_slice($myelements, 30, 8);
        $GLS_group = array_slice($myelements, 39, 13);
        $WR_group = array_slice($myelements, 53, 7);
        $AR_group = array_slice($myelements, 61, 6);
        $NT_group = array_slice($myelements, 68, 6);
        $TST_group = array_slice($myelements, 75, 8);
        $CE_group = array_slice($myelements, 84, 7);
        $TM_group = array_slice($myelements, 92, 10);
        $MM_group = array_slice($myelements, 103, 8);


        //Motivation and Responsibilty

        $MR_sum = 0;
        $MR_improve = '</br> </br><strong><strong> Area(s) with room for improvement:</strong></strong>';

        foreach ($MR_group as $key => $value) {
            if ($this->controller->get($key) !== NULL) {
                $MR_sum = $MR_sum + $this->controller->get($key) - 1;
                if ($this->controller->get($key) < 3) {
                    $MR_improve .= '</br>' . $value['display_name'] . '.';
                }
            }
        }


        echo '<h3> Motivation and Responsibility </h3>';
        if ($MR_sum < 22) {
            echo 'You could take greater ownership in your educational and personal success. Talk to your SSS Advisor about discovering your
               purpose and creating the outcomes you desire.';

            echo $MR_improve;
        } else {
            echo 'Your answers indicate that you are determined to be successful and are taking positive steps to create the outcomes you desire.';

            echo $MR_improve;
        }

        // Organization

        $ORG_sum = 0;
        $ORG_improve = '</br> </br><strong> Area(s) with room for improvement:</strong>';

        foreach ($ORG_group as $key => $value) {

            if ($this->controller->get($key) !== NULL) {
                $ORG_sum = $ORG_sum + $this->controller->get($key) - 1;

                if ($this->controller->get($key) < 3) {
                    $ORG_improve .= '</br>' . $value['display_name'] . '.';
                }
            }
        }

        echo '<h3> Organization </h3>';
        if ($ORG_sum < 14) {
            echo 'You need to identify an effective system for getting organized. Talk to your SSS
                Advisor about specific strategies you can implement.';

            echo $ORG_improve;
        } else {
            echo 'Your answers indicate that you are organized, which is important for your college success.';

            echo $ORG_improve;
        }
        // Self Confidence and Social Interaction

        $SC_SI_sum = 0;
        $SC_SI_improve = '</br> </br><strong> Area(s) with room for improvement:</strong>';

        foreach ($SC_SI_group as $key => $value) {

            if ($this->controller->get($key) !== NULL) {
                $SC_SI_sum = $SC_SI_sum + $this->controller->get($key) - 1;

                if ($this->controller->get($key) < 3) {
                    $SC_SI_improve .= '</br>' . $value['display_name'] . '.';
                }
            }
        }
        echo '<h3>Self Confidence and Social Interaction </h3>';
        if ($SC_SI_sum < 16) {
            echo 'You have an opportunity to strengthen your self-awareness and social connections. Talk
                with your SSS Advisor about confidence-building and relationship-building activities.';

            echo $SC_SI_improve;
        } else {
            echo 'You answers indicate that you have an empowering attitude and mutually supportive relationships.';
            echo $SC_SI_improve;
        }

        // Healthy living

        $HL_sum = 0;
        $HL_improve = '</br> </br><strong> Area(s) with room for improvement:</strong>';

        foreach ($HL_group as $key => $value) {

            if ($this->controller->get($key) !== NULL) {
                $HL_sum = $HL_sum + $this->controller->get($key) - 1;

                if ($this->controller->get($key) < 3) {
                    $HL_improve .= '</br>' . $value['display_name'] . '.';
                }
            }
        }
        echo '<h3> Healthy living </h3>';
        if ($HL_sum < 16) {
            echo 'You could take better care of yourself. Talk with your SSS Advisor about ways to
                strengthen your physical and mental well-being.';

            echo $HL_improve;
        } else {
            echo 'You are tuned into your physical and mental well-being.';
            echo $HL_improve;
        }

        // General learning strategies

        $GLS_sum = 0;
        $GLS_improve = '</br> </br><strong> Area(s) with room for improvement:</strong>';

        foreach ($GLS_group as $key => $value) {

            if ($this->controller->get($key) !== NULL) {
                $GLS_sum = $GLS_sum + $this->controller->get($key) - 1;

                if ($this->controller->get($key) < 3) {
                    $GLS_improve .= '</br>' . $value['display_name'] . '.';
                }
            }
        }
        echo '<h3> General learning strategies </h3>';
        if ($GLS_sum < 26) {
            echo 'You could improve your study skills. Talk with your SSS Advisor about helpful
                resources and strategies.';

            echo $GLS_improve;
        } else {
            echo 'Your answers indicate your study skills will contribute to your learning and success in college.';
            echo $GLS_improve;
        }

        // Writing

        $WR_sum = 0;
        $WR_improve = '</br> </br><strong> Area(s) with room for improvement:</strong>';

        foreach ($WR_group as $key => $value) {

            if ($this->controller->get($key) !== NULL) {
                $WR_sum = $WR_sum + $this->controller->get($key) - 1;

                if ($this->controller->get($key) < 3) {
                    $WR_improve .= '</br>' . $value['display_name'] . '.';
                }
            }
        }
        echo '<h3> Writing </h3>';
        if ($WR_sum < 14) {
            echo 'You could improve your college writing skills. Talk with your SSS Advisor and consider taking advantage of the Writing Center.';

            echo $WR_improve;
        } else {
            echo 'Your answers indicate your writing skills will contribute to your success in college.';
            echo $WR_improve;
        }

        // Active Reading

        $AR_sum = 0;
        $AR_improve = '</br> </br><strong> Area(s) with room for improvement:</strong>';

        foreach ($AR_group as $key => $value) {

            if ($this->controller->get($key) !== NULL) {
                $AR_sum = $AR_sum + $this->controller->get($key) - 1;

                if ($this->controller->get($key) < 3) {
                    $AR_improve .= '</br>' . $value['display_name'] . '.';
                }
            }
        }


        echo '<h3> Active Reading </h3>';

        if ($AR_sum < 12) {
            echo 'You could improve your college reading skills. Talk with your SSS Advisor for more
                information.';

            echo $AR_improve;
        } else {
            echo 'Your answers indicate that you are using appropriate strategies to help you understand
                and learn what you read.';
            echo $AR_improve;
        }

        // Notetaking

        $NT_sum = 0;
        $NT_improve = '</br> </br><strong> Area(s) with room for improvement:</strong>';

        foreach ($NT_group as $key => $value) {

            if ($this->controller->get($key) !== NULL) {
                $NT_sum = $NT_sum + $this->controller->get($key) - 1;

                if ($this->controller->get($key) < 3) {
                    $NT_improve .= '</br>' . $value['display_name'] . '.';
                }
            }
        }
        echo '<h3> Notetaking </h3>';
        if ($NT_sum < 12) {
            echo 'You need to develop more active approaches for taking notes in the classroom. Talk
                with your SSS Advisor about effective note-taking strategies and tools.';

            echo $NT_improve;
        } else {
            echo 'Your answers indicate that your note-taking skills will contribute to your learning and
                success in college.';
            echo $NT_improve;
        }

        // Testing

        $TST_sum = 0;
        $TST_improve = '</br> </br><strong> Area(s) with room for improvement:</strong>';

        foreach ($TST_group as $key => $value) {

            if ($this->controller->get($key) !== NULL) {
                $TST_sum = $TST_sum + $this->controller->get($key) - 1;

                if ($this->controller->get($key) < 3) {
                    $TST_improve .= '</br>' . $value['display_name'] . '.';
                }
            }
        }
        echo '<h3> Testing </h3>';
        if ($TST_sum < 16) {
            echo 'You are not doing all you can to perform well on college exams. Talk with your SSS
                Advisor about test preparation, testing strategies, and/or test anxiety.';

            echo $TST_improve;
        } else {
            echo 'Your answers indicate that you are using appropriate strategies to prepare for and take
                tests.';
            echo $TST_improve;
        }

        // Classroom Engagement

        $CE_sum = 0;
        $CE_improve = '</br> </br><strong> Area(s) with room for improvement:</strong>';

        foreach ($CE_group as $key => $value) {

            if ($this->controller->get($key) !== NULL) {
                $CE_sum = $CE_sum + $this->controller->get($key) - 1;

                if ($this->controller->get($key) < 3) {
                    $CE_improve .= '</br>' . $value['display_name'] . '.';
                }
            }
        }
        echo '<h3> Classroom Engagement </h3>';
        if ($CE_sum < 14) {
            echo 'You are not displaying classroom behaviors characteristic of successful college students.
                Talk with your SSS Advisor.';

            echo $CE_improve;
        } else {
            echo 'You are displaying classroom behaviors characteristic of successful college students.';
            echo $CE_improve;
        }

        // Time Management

        $TM_sum = 0;
        $TM_improve = '</br> </br><strong> Area(s) with room for improvement:</strong>';

        foreach ($TM_group as $key => $value) {

            if ($this->controller->get($key) !== NULL) {
                $TM_sum = $TM_sum + $this->controller->get($key) - 1;

                if ($this->controller->get($key) < 3) {
                    $TM_improve .= '</br>' . $value['display_name'];
                }
            }
        }
        echo '<h3> Time Management </h3>';
        if ($TM_sum < 20) {
            echo 'You need to prioritize and organize your time better. Talk with your SSS Advisor about
                setting priorities and overcoming procrastination.';

            echo $TM_improve;
        } else {
            echo 'Your answers indicate that you are good at utilizing your time.';
            echo $TM_improve;
        }

        // Money  Management

        $MM_sum = 0;
        $MM_improve = '</br> </br><strong> Area(s) with room for improvement:</strong>';

        foreach ($MM_group as $key => $value) {

            if ($this->controller->get($key) !== NULL) {
//          if($this->controller->forms->get_form('StudySkillsAssessmentOneForm')->get_display_name($key) !== NULL){

                $MM_sum = $MM_sum + $this->controller->get($key) - 1;

                if ($this->controller->get($key) < 3) {
                    $MM_improve .= '</br>' . $value['display_name'] . '.';
                }
            }
        }

        echo '<h3> Money  Management </h3>';
        if ($MM_sum < 16) {
            echo 'You need to improve your money management skills. Talk with your SSS Advisor about
                setting a budget and tracking your expenses.';

            echo $MM_improve;
        } else {
            echo 'Your answers indicate you are following wise money management practices.';
            echo $MM_improve;
        }
    }

}

?>
