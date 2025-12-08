# Fix API type parameters in chatrooms.ts
$file = "c:\Users\hyper\workspace\fwber\fwber-frontend\lib\api\chatrooms.ts"
$content = Get-Content $file -Raw

# Define replacements (pattern -> replacement)
$replacements = @{
    'export async function getChatrooms\(filters: ChatroomFilters = \{\}\): Promise<ChatroomResponse> \{[\s\S]*?const response = await apiClient\.get\(`/chatrooms\?\$\{params\.toString\(\)\}`\);' = 
        'export async function getChatrooms(filters: ChatroomFilters = {}): Promise<ChatroomResponse> {
  const params = new URLSearchParams();
  if (filters.page) params.append(''page'', filters.page.toString());
  if (filters.per_page) params.append(''per_page'', filters.per_page.toString());
  if (filters.type) params.append(''type'', filters.type);
  if (filters.category) params.append(''category'', filters.category);
  if (filters.city) params.append(''city'', filters.city);
  if (filters.search) params.append(''search'', filters.search);
  if (filters.sort) params.append(''sort'', filters.sort);

  const response = await apiClient.get<ChatroomResponse>(`/chatrooms?${params.toString()}`)';
    
    'const response = await apiClient\.get\(`/chatrooms/\$\{id\}`\);' = 
        'const response = await apiClient.get<{ chatroom: Chatroom; messages: MessageResponse }>(`/chatrooms/${id}`)';
    
    'const response = await apiClient\.post\(''/chatrooms'', data\);' = 
        'const response = await apiClient.post<Chatroom>(''/chatrooms'', data)';
    
    'const response = await apiClient\.post\(`/chatrooms/\$\{id\}/join`\);' = 
        'const response = await apiClient.post<{ message: string }>(`/chatrooms/${id}/join`)';
    
    'const response = await apiClient\.post\(`/chatrooms/\$\{id\}/leave`\);' = 
        'const response = await apiClient.post<{ message: string }>(`/chatrooms/${id}/leave`)';
    
    'const response = await apiClient\.get\(`/chatrooms/\$\{id\}/members`\);' = 
        'const response = await apiClient.get<MemberResponse>(`/chatrooms/${id}/members`)';
    
    'const response = await apiClient\.put\(`/chatrooms/\$\{id\}`, data\);' = 
        'const response = await apiClient.put<Chatroom>(`/chatrooms/${id}`, data)';
    
    'const response = await apiClient\.delete\(`/chatrooms/\$\{id\}`\);' = 
        'const response = await apiClient.delete<{ message: string }>(`/chatrooms/${id}`)';
    
    'const response = await apiClient\.get\(''/chatrooms/my''\);' = 
        'const response = await apiClient.get<Chatroom[]>(''/chatrooms/my'')';
    
    'const response = await apiClient\.get\(''/chatrooms/categories''\);' = 
        'const response = await apiClient.get<string[]>(''/chatrooms/categories'')';
    
    'const response = await apiClient\.get\(''/chatrooms/popular''\);' = 
        'const response = await apiClient.get<Chatroom[]>(''/chatrooms/popular'')';
}

foreach ($pattern in $replacements.Keys) {
    $content = $content -replace $pattern, $replacements[$pattern]
}

Set-Content -Path $file -Value $content
Write-Output "Fixed type parameters in chatrooms.ts"
