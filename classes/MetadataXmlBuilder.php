<?php

namespace APP\plugins\generic\scholarOneIntegration\classes;

use DOMDocument;
use APP\submission\Submission;
use PKP\db\DAORegistry;

class MetadataXmlBuilder
{
    private $clientKey;
    private $journalShortName;

    public function __construct(string $clientKey, string $journalShortName)
    {
        $this->clientKey = $clientKey;
        $this->journalShortName = $journalShortName;
    }

    public function createMetadataXml(Submission $submission, string $xmlFilePath): void
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $articleNode = $dom->createElement('article');
        $dom->appendChild($articleNode);

        $articleNode->setAttribute('xmlns:oasis', 'http://www.niso.org/standards/z39-96/ns/oasis-exchange/table');
        $articleNode->setAttribute('xmlns:xlink', 'http://www.w3.org/1999/xlink');
        $articleNode->setAttribute('xmlns:mml', 'http://www.w3.org/1998/Math/MathML');
        $articleNode->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');

        $frontNode = $dom->createElement('front');
        $articleNode->appendChild($frontNode);

        $journalMetaNode = $this->createJournalMetaNode($dom);
        $frontNode->appendChild($journalMetaNode);

        $articleMeta = $this->createArticleMetaNode($dom, $submission);
        $frontNode->appendChild($articleMeta);

        $dom->save($xmlFilePath);
    }

    private function createJournalMetaNode($dom)
    {
        $journalMetaNode = $dom->createElement('journal-meta');

        $journalIdNode = $dom->createElement('journal-id');
        $journalIdNode->setAttribute('journal-id-type', 'publisher');
        $journalIdNode->appendChild($dom->createTextNode($this->clientKey));

        $journalMetaNode->appendChild($journalIdNode);

        $journalTitleGroupNode = $dom->createElement('journal-title-group');
        $journalTitleNode = $dom->createElement('journal-title');
        $journalTitleNode->appendChild($dom->createTextNode($this->journalShortName));
        $journalTitleGroupNode->appendChild($journalTitleNode);

        $journalMetaNode->appendChild($journalTitleGroupNode);

        return $journalMetaNode;
    }

    private function createArticleMetaNode($dom, $submission)
    {
        $publication = $submission->getCurrentPublication();
        $locale = $submission->getData('locale');
        $articleMetaNode = $dom->createElement('article-meta');

        $titleGroupNode = $dom->createElement('title-group');
        $articleTitleNode = $dom->createElement('article-title');
        $fullTitle = $publication->getLocalizedFullTitle($locale);
        $articleTitleNode->appendChild($dom->createTextNode($fullTitle));
        $titleGroupNode->appendChild($articleTitleNode);
        $articleMetaNode->appendChild($titleGroupNode);

        $abstractNode = $dom->createElement('abstract');
        $paragraph = $dom->createElement('p');
        $paragraph->appendChild($dom->createTextNode($publication->getLocalizedData('abstract', $locale)));
        $abstractNode->appendChild($paragraph);
        $articleMetaNode->appendChild($abstractNode);

        $keywordsGroupsNode = $this->createKeywordsGroupNode($dom, $submission);
        $articleMetaNode->appendChild($keywordsGroupsNode);

        return $articleMetaNode;
    }

    private function createKeywordsGroupNode($dom, $submission)
    {
        $keywordsNode = $dom->createElement('kwd-group');
        $keywordsNode->setAttribute('kwd-group-type', 'Keywords');
        $keywordsNode->setAttribute('id', '');

        $publication = $submission->getCurrentPublication();
        $submissionKeywordDao = DAORegistry::getDAO('SubmissionKeywordDAO');
        $keywords = $submissionKeywordDao->getKeywords($publication->getId());
        $submissionLocale = $submission->getData('locale');

        foreach ($keywords[$submissionLocale] as $keyword) {
            $keywordNode = $dom->createElement('kwd');
            $keywordNode->setAttribute('id', '');
            $keywordNode->appendChild($dom->createTextNode($keyword));
            $keywordsNode->appendChild($keywordNode);
        }

        return $keywordsNode;
    }
}
