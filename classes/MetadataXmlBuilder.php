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

    public function createMetadataXml(Submission $submission, array $galleys, string $xmlFilePath): void
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

        $articleMeta = $this->createArticleMetaNode($dom, $submission, $galleys);
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

    private function createArticleMetaNode($dom, $submission, $galleys)
    {
        $publication = $submission->getCurrentPublication();
        $locale = $submission->getData('locale');
        $articleMetaNode = $dom->createElement('article-meta');

        $doiObject = $publication->getData('doiObject');
        if ($doiObject) {
            $articleIdNode = $dom->createElement('article-id', $doiObject->getData('doi'));
            $articleIdNode->setAttribute('pub-id-type', 'doi');
            $articleMetaNode->appendChild($articleIdNode);
        }

        $articleCategoriesNode = $dom->createElement('article-categories');
        $subjGroupNode = $dom->createElement('subj-group');
        $subjGroupNode->setAttribute('subj-group-type', 'Manuscript Type');
        $subjectNode = $dom->createElement('subject', 'Original Article');
        $subjGroupNode->appendChild($subjectNode);
        $articleCategoriesNode->appendChild($subjGroupNode);
        $articleMetaNode->appendChild($articleCategoriesNode);

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

        $contributorsGroupNode = $this->createContributorsGroupNode($dom, $submission);
        $articleMetaNode->appendChild($contributorsGroupNode);

        foreach ($galleys as $galley) {
            $supplementaryMaterialNode = $this->createSupplementaryMaterialNode($dom, $galley);
            $articleMetaNode->appendChild($supplementaryMaterialNode);
        }

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

    private function createContributorsGroupNode($dom, $submission)
    {
        $contributorsGroupNode = $dom->createElement('contrib-group');

        $publication = $submission->getCurrentPublication();
        $primaryContactId = $publication->getData('primaryContactId');
        $authors = $publication->getData('authors');
        $indexAuthor = 0;
        $affiliations = [];

        foreach ($authors as $author) {
            $contributorNode = $dom->createElement('contrib');
            $contributorNode->setAttribute('contrib-type', 'author');
            $contributorNode->setAttribute('corresp', ($author->getId() === $primaryContactId) ? 'yes' : 'no');

            $roleNode = $dom->createElement('role');
            $roleNode->setAttribute('content-type', $indexAuthor + 1);
            $contributorNode->appendChild($roleNode);

            $nameNode = $dom->createElement('name');
            $givenNameNode = $dom->createElement('given-names', $author->getLocalizedData('givenName'));
            $familyNameNode = $dom->createElement('surname', $author->getLocalizedData('familyName'));
            $nameNode->appendChild($givenNameNode);
            $nameNode->appendChild($familyNameNode);
            $contributorNode->appendChild($nameNode);

            $emailNode = $dom->createElement('email', $author->getData('email'));
            $contributorNode->appendChild($emailNode);

            $affiliation = $author->getLocalizedData('affiliation');
            if ($affiliation) {
                $affiliations[] = $affiliation;
                $xrefNode = $dom->createElement('xref');
                $xrefNode->setAttribute('ref-type', 'aff');
                $xrefNode->setAttribute('rid', 'aff' . count($affiliations));
                $contributorNode->appendChild($xrefNode);
            }

            $contributorsGroupNode->appendChild($contributorNode);
            $indexAuthor++;
        }

        foreach ($affiliations as $index => $affiliation) {
            $affNode = $dom->createElement('aff');
            $affNode->setAttribute('id', 'aff' . ($index + 1));

            $institutionNode = $dom->createElement('institution');
            $institutionNode->appendChild($dom->createTextNode($affiliation));
            $affNode->appendChild($institutionNode);
            $contributorsGroupNode->appendChild($affNode);
        }

        return $contributorsGroupNode;
    }

    private function createSupplementaryMaterialNode($dom, $galley)
    {
        $galleyLocale = $galley->getData('locale');
        $submissionFile = $galley->getFile();
        $fileName = $submissionFile->getLocalizedData('name', $galleyLocale);

        $supplementaryMaterialNode = $dom->createElement('supplementary-material');
        $supplementaryMaterialNode->setAttribute('content-type', 'Main Document');
        $supplementaryMaterialNode->setAttribute('xlink:href', $fileName);

        return $supplementaryMaterialNode;
    }
}
