<?php

use Grido\Grid,
    Grido\Components\Filters\Filter,
    Grido\Components\Columns\Column,
    Nette\Utils\Html;

/**
 * Example presenter.
 *
 * @package     Grido
 * @author      Petr Bugyík
 */
final class ExamplePresenter extends BasePresenter
{
    /** @var string @persistent - only for demo */
    public $filterRenderType = Filter::RENDER_INNER;

    protected function createComponentGrid($name)
    {
        $grid = new Grid($this, $name);

        $fluent = dibi::select('u.*, c.title AS country')
            ->from('[user] u')
            ->join('[country] c')->on('u.country_code = c.code');
        $grid->setModel($fluent);
        //$grid->setModel($this->context->sqlite->table('user'));

        $grid->addColumn('firstname', 'Firstname')
            ->setFilter()
                ->setSuggestion();

        $grid->addColumn('surname', 'Surname')
            ->setSortable()
            ->setFilter()
                ->setSuggestion();

        $grid->addColumn('gender', 'Gender')
            ->setSortable()
            ->cellPrototype->class[] = 'center';

        $grid->addColumn('birthday', 'Birthday', Column::TYPE_DATE)
            ->setDateFormat(Grido\Components\Columns\Date::FORMAT_TEXT)
            ->setSortable()
            ->setFilter(Filter::TYPE_DATE)
                ->setCondition(Filter::CONDITION_CALLBACK, callback($this, 'gridBirthdayFilterCondition'));
        $grid->getColumn('birthday')->cellPrototype->class[] = 'center';

        $baseUri = $this->template->baseUri;
        $grid->addColumn('country', 'Country')
            ->setSortable()
            ->setCustomRender(function($item) use($baseUri) {
                $img = Html::el('img')->src("$baseUri/img/flags/$item->country_code.gif");
                return "$img $item->country";
            })
            ->setFilter()
                ->setSuggestion();

        $grid->addColumn('card', 'Card')
            ->setSortable()
            ->setColumn('cctype') //name of db column
            ->setReplacement(array('MasterCard' => Html::el('b')->setText('MasterCard')))
            ->cellPrototype->class[] = 'center';

        $grid->addColumn('emailaddress', 'Email', Column::TYPE_MAIL)
            ->setSortable()
            ->setFilter();

        $grid->addColumn('centimeters', 'Height')
            ->setSortable()
            ->setFilter(Filter::TYPE_NUMBER);
        $grid->getColumn('centimeters')->cellPrototype->class[] = 'center';

        $grid->addFilter('gender', 'Gender', Filter::TYPE_SELECT, array(
            '' => '',
            'female' => 'female',
            'male' => 'male'
        ));

        $grid->addFilter('card', 'Card', Filter::TYPE_SELECT, array(
                '' => '',
                'MasterCard' => 'MasterCard',
                'Visa' => 'Visa'
            ))
            ->setColumn('cctype');

        $grid->addFilter('preferred', 'Only preferred girls :)', Filter::TYPE_CHECK)
            ->setCondition(Filter::CONDITION_CUSTOM, array(
                TRUE => '[gender] = "female" AND [centimeters] >= 170' //for checked
        ));

        $grid->addAction('edit', 'Edit')
            ->setIcon('pencil');

        $grid->addAction('delete', 'Delete')
            ->setIcon('trash')
            ->setConfirm('Are you sure you want to delete this item?');

        $operations = array('print' => 'Print', 'delete' => 'Delete');
        $grid->setOperations($operations, callback($this, 'gridOperationsHandler'))
            ->setConfirm('delete', 'Are you sure you want to delete %i items?');

        $grid->setFilterRenderType($this->filterRenderType);
        $grid->setExporting();
    }

    /**
     * Handler for operations.
     * @param string $operation
     * @param array $id
     */
    public function gridOperationsHandler($operation, $id)
    {
        if ($id) {
            $row = implode(', ', $id);
            $this->flashMessage("Process operation '$operation' for row with id: $row...", 'info');
        } else {
            $this->flashMessage('No rows selected.', 'error');
        }

        $this->redirect($operation, array('id' => $id));
    }

    /**
     * Custom condition callback for filter birthday.
     * @param string $value
     * @return array|NULL
     */
    public function gridBirthdayFilterCondition($value)
    {
        $date = explode('.', $value);
        foreach ($date as &$val) {
            $val = (int) $val;
        }

        return count($date) == 3
            ? array('[birthday] = %s', "{$date[2]}-{$date[1]}-{$date[0]}")
            : NULL;
    }

    public function actionEdit($id)
    {
        $this->flashMessage("Action '$this->action' for row with id: $id done.", 'success');
        $this->redirect('default');
    }

    public function actionDelete()
    {
        $id = $this->getParam('id');
        $id = is_array($id) ? implode(', ', $id) : $id;
        $this->flashMessage("Action '$this->action' for row with id: $id done.", 'success');
        $this->redirect('default');
    }

    public function actionPrint()
    {
        $id = $this->getParam('id');
        $id = is_array($id) ? implode(', ', $id) : $id;
        $this->flashMessage("Action '$this->action' for row with id: $id done.", 'success');
        $this->redirect('default');
    }
}
