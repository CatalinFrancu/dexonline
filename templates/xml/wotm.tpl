{assign var="row" value=$searchResult|default:null}
<?xml version="1.0" encoding="UTF-8" ?>
<results>
    <month>{$month}</month>
    <requested>
        <record>
            <year>{$year}</year>
            <word>{$row->definition->lexicon}</word>
            <reason>{$reason|escape:html}</reason>
            <image>{$imageUrl}</image>
            <imageAuthor>{$artist->name|default:''|escape:html}</imageAuthor>
            <definition>
                <id>{$row->definition->id}</id>
                <internalRep>{$row->definition->internalRep|escape:html}</internalRep>
                <htmlRep>{HtmlConverter::convert($row->definition)}</htmlRep>
                <userNick>{$row->user->nick}</userNick>
                <sourceName>{$row->source->shortName}</sourceName>
                <createDate>{$row->definition->createDate}</createDate>
                <modDate>{$row->definition->modDate}</modDate>
            </definition>
        </record>
    </requested>
</results>
