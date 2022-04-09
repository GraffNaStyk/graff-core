<?php

namespace App\Facades\Html;

class Html
{
	private array $metaTags = [];
	
	private ?string $title = null;
	
	private array $otherTags = [];
	
	public function setMetaTag(string $name, string $content): void
	{
		$this->metaTags[$name] = trim($content);
	}
	
	public function getMetaTags(): ?array
	{
		return $this->metaTags;
	}
	
	public function getMetaTag(string $name): ?string
	{
		return $this->metaTags[$name] ?: null;
	}
	
	public function setTitle(string $title): void
	{
		$this->title = $title;
	}
	
	public function getTitle(): ?string
	{
		return $this->title;
	}
	
	public function setTag(string $tagName, string $tagValueName, string $tagValue, array $additionalParams=[]): void
	{
		$this->otherTags[] = [
			'tagName'          => $tagName,
			'tagValueName'     => $tagValueName,
			'tagValue'         => $tagValue,
			'additionalParams' => $additionalParams
		];
	}
	
	public function getOtherTags(): array
	{
		return $this->otherTags;
	}
}
