<?php

//ini_set('display_errors', 1);
//ini_set('display_startup_erros', 1);
//error_reporting(E_ALL);

class DoencaBaseDetalhe extends TPage
{
    private $form;
    private $datagrid;
    private $pageNavigation;
    private $loaded;
    public function __construct()
    {
        parent::__construct();

        $this->form = new BootstrapFormBuilder( "form_list_doeca_base" );
        $this->form->setFormTitle( "Detalhamento das Doenças Base" );
        $this->form->class = "tform";

        $id = new THidden( "id" );
        $cid_id = new TCombo("cid_id");
        $paciente_id = new TLabel( "paciente_id" );

        $paciente_id->setValue(filter_input(INPUT_GET, 'id'));
        TTransaction::open('dbsic');
        $tempVisita = new PacienteRecord( filter_input( INPUT_GET, 'id' ) );

        //----------------------------------------------------------------------------------------------------

        $items = array();
        TTransaction::open('dbsic');
        $repository = new TRepository('CidRecord');

        $criteria = new TCriteria;
        $criteria->setProperty('order', 'nome');

        $cadastros = $repository->load($criteria);

        foreach ($cadastros as $object) {
            $items[$object->id] = $object->nome;
        }

        $cid_id->addItems($items);
        TTransaction::close();


        //----------------------------------------------------------------------------------------------------

        if( $tempVisita ){
            $paciente_nome = new TLabel( $tempVisita->nome );
            $paciente_nome->setEditable(FALSE);
        }
        TTransaction::close();


        $this->form->addFields( [new TLabel('Paciente: '), $paciente_nome] );

        $this->form->addFields( [ new TLabel( "CID:" ), $cid_id ] );

        $this->form->addFields( [ $id ] );

        $action = new TAction(array($this, 'onSave'));
        $action->setParameter('fk', '' . filter_input(INPUT_GET, 'fk') . '');

        $this->form->addAction('Salvar', $action, 'fa:floppy-o');
        $this->form->addAction('Voltar para Pacientes',new TAction(array('PacienteList','onReload')),'fa:table blue');

        $this->datagrid = new BootstrapDatagridWrapper( new TDataGrid() );
        $this->datagrid->datatable = "true";
        $this->datagrid->style = "width: 100%";
        $this->datagrid->setHeight( 320 );

        $column_cidid = new TDataGridColumn( "cid_id", "CID", "left" );


        $this->datagrid->addColumn( $column_cidid );

        $order_cidid = new TAction( [ $this, "onReload" ] );
        $order_cidid->setParameter( "order", "id" );
        $column_cidid->setAction( $order_cidid );


        $action_edit = new TDataGridAction( [ $this, "onEdit" ] );
        $action_edit->setButtonClass( "btn btn-default" );
        $action_edit->setLabel( "Editar" );
        $action_edit->setImage( "fa:pencil-square-o blue fa-lg" );
        $action_edit->setField( "id" );
        $this->datagrid->addAction( $action_edit );

        $action_del = new TDataGridAction( [ $this, "onDelete" ] );
        $action_del->setButtonClass( "btn btn-default" );
        $action_del->setLabel( "Deletar" );
        $action_del->setImage( "fa:trash-o red fa-lg" );
        $action_del->setField( "id" );
        $this->datagrid->addAction( $action_del );

        $this->datagrid->createModel();

        $this->pageNavigation = new TPageNavigation();
        $this->pageNavigation->setAction( new TAction( [ $this, "onReload" ] ) );
        $this->pageNavigation->setWidth( $this->datagrid->getWidth() );


        $container = new TVBox();
        $container->style = "width: 90%";
        $container->add( new TXMLBreadCrumb( "menu.xml", "PacienteList" ) );
        $container->add( $this->form );
        $container->add( TPanelGroup::pack( NULL, $this->datagrid ) );
        $container->add( $this->pageNavigation );


        parent::add( $container );
    }
    public function onSave()
    {
        try
        {
            $this->form->validate();
            TTransaction::open( "dbsic" );

            $object = $this->form->getData( "DoencaBaseRecord" );
            $object->paciente_id =  filter_input(INPUT_GET, 'id');
            $object->store();


            TTransaction::close();
            $action = new TAction( [ $this , "onReload" ] );
            new TMessage( "info", "Registro salvo com sucesso!", $action );
        }
        catch ( Exception $ex )
        {
            TTransaction::rollback();
            new TMessage( "error", "Ocorreu um erro ao tentar salvar o registro!<br><br>" . $ex->getMessage() );
        }
    }
    public function onEdit( $param )
    {
        try
        {
            if( isset( $param[ "id" ] ) )
            {
                TTransaction::open( "dbsic" );
                $object = new DoencaBaseRecord( $param[ "id" ] );
                $this->form->setData( $object );
                TTransaction::close();
            }
        }
        catch ( Exception $ex )
        {
            TTransaction::rollback();
            new TMessage( "error", "Ocorreu um erro ao tentar carregar o registro para edição!<br><br>" . $ex->getMessage() );
        }
    }
    public function onReload( $param = NULL )
    {
        try
        {

            TTransaction::open( "dbsic" );


            $repository = new TRepository( "DoencaBaseRecord" );

            if ( empty( $param[ "order" ] ) )
            {
                $param[ "order" ] = "id";
                $param[ "direction" ] = "asc";
            }
            $limit = 10;


            $criteria = new TCriteria();
            $criteria->add(new TFilter('paciente_id', '=', filter_input(INPUT_GET, 'fk')));
            $criteria->setProperties( $param );
            $criteria->setProperty( "limit", $limit );

            $objects = $repository->load( $criteria, FALSE );

            $this->datagrid->clear();


            if ( !empty( $objects ) )
            {
                foreach ( $objects as $object )
                {
                    $this->datagrid->addItem( $object );
                }
            }
            $criteria->resetProperties();

            $count = $repository->count($criteria);
            $this->pageNavigation->setCount($count);
            $this->pageNavigation->setProperties($param);
            $this->pageNavigation->setLimit($limit);

            TTransaction::close();
            $this->loaded = true;
        }
        catch ( Exception $ex )
        {
            TTransaction::rollback();
            new TMessage( "error", $ex->getMessage() );
        }
    }

    public function onDelete( $param = NULL )
    {
        if( isset( $param[ "key" ] ) )
        {

            $action1 = new TAction( [ $this, "Delete" ] );
            $action2 = new TAction( [ $this, "onReload" ] );

            $action1->setParameter( "key", $param[ "key" ] );
            new TQuestion( "Deseja realmente apagar o registro?", $action1, $action2 );
        }
    }
    function Delete( $param = NULL )
    {
        try
        {
            TTransaction::open( "dbsic" );
            $object = new DoencaBaseRecord( $param[ "key" ] );
            $object->delete();
            TTransaction::close();
            $this->onReload();
            new TMessage("info", "Registro apagado com sucesso!");
        }
        catch ( Exception $ex )
        {
            TTransaction::rollback();
            new TMessage("error", $ex->getMessage());
        }
    }
    public function show()
    {
        $this->onReload();

        parent::show();
    }
}
